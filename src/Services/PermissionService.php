<?php

namespace Mca\Permission\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mca\Permission\Models\Permission;
use Mca\Permission\Models\Role;
use Mca\Permission\Support\PermissionGrantContext;
use Mca\Permission\Support\PermissionLabels;
use Mca\Permission\Support\PermissionMode;

class PermissionService
{
    public function __construct(
        private readonly GrantResolverRegistry $grantRegistry,
    ) {}

    public function userRoleId(?Authenticatable $user): ?int
    {
        if (! $user) {
            return null;
        }

        $column = config('permission.user_role_column', 'role_id');
        $value = $user->{$column} ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function isRoot(?Authenticatable $user): bool
    {
        $roleId = $this->userRoleId($user);
        if ($roleId === null) {
            return false;
        }

        return Role::query()
            ->whereKey($roleId)
            ->where('is_root', true)
            ->exists();
    }

    /**
     * @return array{folder: string, controller: string, method: string}|null
     */
    public function resolveFromControllerClass(?string $class, string $method): ?array
    {
        if ($class === null) {
            return null;
        }

        $controller = class_basename($class);
        if (! str_ends_with($controller, 'Controller')) {
            return null;
        }

        $folder = $this->resolveFolderForControllerClass($class);
        if ($folder === null) {
            return null;
        }

        return [
            'folder' => $folder,
            'controller' => $controller,
            'method' => $method,
        ];
    }

    private function resolveFolderForControllerClass(string $class): ?string
    {
        $segments = app(ScanSegmentService::class)->activeSegments();

        $folder = null;
        $bestLength = -1;

        foreach ($segments as $segment) {
            $namespace = rtrim((string) ($segment['namespace'] ?? ''), '\\');
            if ($namespace === '') {
                continue;
            }

            $prefix = $namespace.'\\';
            if ($class !== $namespace && ! str_starts_with($class, $prefix)) {
                continue;
            }

            $length = strlen($namespace);
            if ($length > $bestLength) {
                $bestLength = $length;
                $folder = (string) ($segment['folder'] ?? '');
            }
        }

        if ($folder !== null && $folder !== '') {
            return $folder;
        }

        if (str_contains($class, '\\Api\\')) {
            return 'Api';
        }

        if (str_contains($class, '\\Panel\\')) {
            return 'Panel';
        }

        return null;
    }

    public function canAccess(?Authenticatable $user, string $folder, string $controller, ?string $method = null): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isRoot($user)) {
            return true;
        }

        $controller = $this->normalizeController($controller);

        if ($method === null) {
            return Permission::query()
                ->where('folder', $folder)
                ->where('controller', $controller)
                ->where('is_root_only', false)
                ->get()
                ->contains(fn (Permission $p) => $this->hasGrant($user, $p));
        }

        $permission = Permission::query()
            ->forRoute($folder, $controller, $method)
            ->first();

        if (! $permission || $permission->is_root_only) {
            return false;
        }

        return $this->hasGrant($user, $permission);
    }

    public function hasGrant(Authenticatable $user, Permission $permission): bool
    {
        if ($permission->is_root_only) {
            return false;
        }

        foreach ($this->grantRegistry->resolvers($user) as $resolver) {
            if ($resolver->grants($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function roleHasMethod(int $roleId, string $folder, string $controller, string $method): bool
    {
        if ($this->isRootRoleId($roleId)) {
            return true;
        }

        $permission = Permission::query()
            ->forRoute($folder, $this->normalizeController($controller), $method)
            ->first();

        if (! $permission || $permission->is_root_only) {
            return false;
        }

        return DB::table('role_permission')
            ->where('permission_id', $permission->id)
            ->where('role_id', $roleId)
            ->exists();
    }

    private function roleHasAnyMethod(int $roleId, string $folder, string $controller): bool
    {
        if ($this->isRootRoleId($roleId)) {
            return true;
        }

        return Permission::query()
            ->where('folder', $folder)
            ->where('controller', $this->normalizeController($controller))
            ->where('is_root_only', false)
            ->whereExists(function ($q) use ($roleId) {
                $q->selectRaw('1')
                    ->from('role_permission')
                    ->whereColumn('role_permission.permission_id', 'permissions.id')
                    ->where('role_permission.role_id', $roleId);
            })
            ->exists();
    }

    private function isRootRoleId(int $roleId): bool
    {
        return Role::query()
            ->whereKey($roleId)
            ->where('is_root', true)
            ->exists();
    }

    public function normalizeController(string $controller): string
    {
        return str_ends_with($controller, 'Controller') ? $controller : $controller.'Controller';
    }

    /**
     * @return array{name: string, folder: string, controller: string, module: string, method: string, module_description: string, method_description: string}
     */
    public function buildPermissionMeta(string $folder, string $controller, string $method, ?string $moduleDescription = null): array
    {
        $controller = $this->normalizeController($controller);
        $base = str_replace('Controller', '', $controller);
        $module = strtolower($folder).ucfirst($base);
        $name = $module.'.'.$method;

        return [
            'name' => $name,
            'folder' => $folder,
            'controller' => $controller,
            'module' => $module,
            'method' => $method,
            'module_description' => $moduleDescription ?? self::controllerDescription($folder, $controller),
            'method_description' => self::methodLabel($method),
        ];
    }

    public function syncPermissionLabels(): int
    {
        $updated = 0;

        Permission::query()->each(function (Permission $p) use (&$updated) {
            $moduleDesc = self::controllerDescription($p->folder, $p->controller);
            $methodDesc = self::methodLabel($p->method);

            if ($p->module_description === $moduleDesc && $p->method_description === $methodDesc) {
                return;
            }

            $p->update([
                'module_description' => $moduleDesc,
                'method_description' => $methodDesc,
            ]);
            $updated++;
        });

        return $updated;
    }

    public function flushCache(): void
    {
        Cache::forget(config('permission.cache_key', 'mca.permission.version'));
        $this->grantRegistry->flush();
    }

    public function createFromInput(array $data): Permission
    {
        $meta = $this->buildPermissionMeta(
            (string) $data['folder'],
            (string) $data['controller'],
            (string) $data['method'],
            $data['module_description'] ?? null,
        );

        if (! empty($data['method_description'])) {
            $meta['method_description'] = (string) $data['method_description'];
        }

        $permission = Permission::query()->create([
            'name' => $meta['name'],
            'folder' => $meta['folder'],
            'controller' => $meta['controller'],
            'module' => $meta['module'],
            'method' => $meta['method'],
            'module_description' => $meta['module_description'],
            'method_description' => $meta['method_description'],
            'is_root_only' => (bool) ($data['is_root_only'] ?? false),
        ]);

        $this->flushCache();

        return $permission;
    }

    public function updatePermission(Permission $permission, array $data): Permission
    {
        $meta = $this->buildPermissionMeta(
            (string) $data['folder'],
            (string) $data['controller'],
            (string) $data['method'],
            $data['module_description'] ?? null,
        );

        if (! empty($data['method_description'])) {
            $meta['method_description'] = (string) $data['method_description'];
        }

        $permission->update([
            'name' => $meta['name'],
            'folder' => $meta['folder'],
            'controller' => $meta['controller'],
            'module' => $meta['module'],
            'method' => $meta['method'],
            'module_description' => $meta['module_description'],
            'method_description' => $meta['method_description'],
            'is_root_only' => (bool) ($data['is_root_only'] ?? false),
        ]);

        $this->flushCache();

        return $permission->fresh();
    }

    public function deletePermission(Permission $permission): bool
    {
        $deleted = (bool) $permission->delete();
        if ($deleted) {
            $this->flushCache();
        }

        return $deleted;
    }

    /** @return list<int> */
    public function permissionIdsForRole(int $roleId): array
    {
        if ($this->isRootRoleId($roleId)) {
            return [];
        }

        return DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_permission.role_id', $roleId)
            ->where('permissions.is_root_only', false)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** UI için rol izinleri (root rolünde tüm düzenlenebilir izinler). @return list<int> */
    public function permissionIdsForRoleDisplay(int $roleId): array
    {
        if ($this->isRootRoleId($roleId)) {
            return Permission::query()
                ->where('is_root_only', false)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->permissionIdsForRole($roleId);
    }

    /** @param  list<int>  $permissionIds */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        if ($this->isRootRoleId($roleId)) {
            return;
        }

        $this->syncPivotPermissions('role_permission', 'role_id', $roleId, $permissionIds);
    }

    /** @return list<int> */
    public function permissionIdsForUser(int|string $userId): array
    {
        if (! PermissionMode::supportsUserGrants()) {
            return [];
        }

        return DB::table('user_permission')
            ->join('permissions', 'permissions.id', '=', 'user_permission.permission_id')
            ->where('user_permission.user_id', $userId)
            ->where('permissions.is_root_only', false)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param  list<int>  $permissionIds */
    public function syncUserPermissions(int|string $userId, array $permissionIds, ?bool $exclusive = null): bool
    {
        if (! PermissionMode::supportsUserGrants()) {
            return false;
        }

        $this->syncPivotPermissions('user_permission', 'user_id', $userId, $permissionIds);

        if ($exclusive !== null && ! $this->setUserExclusive($userId, $exclusive)) {
            return false;
        }

        return true;
    }

    /** @return list<int> */
    public function permissionIdsForDepartment(int|string $departmentId): array
    {
        if (! PermissionMode::supportsDepartmentGrants()) {
            return [];
        }

        return DB::table('department_permission')
            ->join('permissions', 'permissions.id', '=', 'department_permission.permission_id')
            ->where('department_permission.department_id', $departmentId)
            ->where('permissions.is_root_only', false)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param  list<int>  $permissionIds */
    public function syncDepartmentPermissions(int|string $departmentId, array $permissionIds, ?bool $exclusive = null): bool
    {
        if (! PermissionMode::supportsDepartmentGrants()) {
            return false;
        }

        $this->syncPivotPermissions('department_permission', 'department_id', $departmentId, $permissionIds);

        if ($exclusive !== null && ! $this->setDepartmentExclusive($departmentId, $exclusive)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     directIds: list<int>,
     *     roleIds: list<int>,
     *     departmentIds: list<int>,
     *     exclusive: bool,
     *     departmentExclusive: bool
     * }
     */
    public function userPermissionMatrix(Authenticatable $user): array
    {
        $userId = $user->getAuthIdentifier();
        $roleId = $this->userRoleId($user);
        $deptId = PermissionGrantContext::userDepartmentId($user);

        $departmentExclusive = false;
        if ($deptId !== null && PermissionMode::supportsDepartmentGrants()) {
            $model = config('permission.department.model');
            if (is_string($model) && class_exists($model)) {
                $department = $model::query()->find($deptId);
                $departmentExclusive = PermissionGrantContext::isDepartmentExclusive($department);
            }
        }

        $role = $roleId ? Role::query()->find($roleId) : null;

        return [
            'directIds' => $userId ? $this->permissionIdsForUser($userId) : [],
            'roleIds' => $roleId ? $this->permissionIdsForRoleDisplay($roleId) : [],
            'departmentIds' => $deptId ? $this->permissionIdsForDepartment($deptId) : [],
            'exclusive' => PermissionGrantContext::isUserExclusive($user),
            'departmentExclusive' => $departmentExclusive,
            'roleId' => $roleId,
            'roleLabel' => $role?->name,
        ];
    }

    public function isUserExclusive(int|string $userId): bool
    {
        $userModel = config('permission.user_model');
        $user = $userModel::query()->find($userId);

        return $user ? PermissionGrantContext::isUserExclusive($user) : false;
    }

    public function isDepartmentExclusive(int|string $departmentId): bool
    {
        $model = config('permission.department.model');
        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        $department = $model::query()->find($departmentId);

        return PermissionGrantContext::isDepartmentExclusive($department);
    }

    public function setUserExclusive(int|string $userId, bool $exclusive): bool
    {
        if (! PermissionGrantContext::userHasExclusiveColumn()) {
            return false;
        }

        $userModel = config('permission.user_model');
        $column = PermissionGrantContext::userExclusiveColumn();
        $userModel::query()->whereKey($userId)->update([$column => $exclusive]);
        $this->flushCache();

        return true;
    }

    public function setDepartmentExclusive(int|string $departmentId, bool $exclusive): bool
    {
        if (! PermissionGrantContext::departmentHasExclusiveColumn()) {
            return false;
        }

        $model = config('permission.department.model');
        $column = PermissionGrantContext::departmentExclusiveColumn();
        $model::query()->whereKey($departmentId)->update([$column => $exclusive]);
        $this->flushCache();

        return true;
    }

    /** @param  list<int>  $permissionIds */
    private function syncPivotPermissions(string $table, string $ownerColumn, int|string $ownerId, array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $validIds = Permission::query()
            ->where('is_root_only', false)
            ->whereIn('id', $permissionIds)
            ->pluck('id')
            ->all();

        DB::table($table)->where($ownerColumn, $ownerId)->delete();

        $now = now();
        foreach ($validIds as $pid) {
            DB::table($table)->insert([
                $ownerColumn => $ownerId,
                'permission_id' => $pid,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flushCache();
    }

    /**
     * @return list<array{key: string, folder: string, controller: string, module: string, module_label: string, items: list<array{id: int, name: string, method: string, method_label: string}>}>
     */
    public function groupedEditablePermissions(): array
    {
        $rows = Permission::query()
            ->where('is_root_only', false)
            ->orderBy('folder')
            ->orderBy('controller')
            ->orderBy('method')
            ->get();

        $groups = [];
        foreach ($rows as $p) {
            $key = $p->folder.'|'.$p->controller;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'folder' => $p->folder,
                    'controller' => $p->controller,
                    'module' => $p->module,
                    'module_label' => $p->module_description ?: self::moduleLabel($p->folder, $p->controller),
                    'items' => [],
                ];
            }
            $groups[$key]['items'][] = [
                'id' => $p->id,
                'name' => $p->name,
                'method' => $p->method,
                'method_label' => $p->method_description ?: self::methodLabel($p->method),
            ];
        }

        return array_values($groups);
    }

    public static function moduleLabel(string $folder, string $controller): string
    {
        return PermissionLabels::moduleLabel($folder, $controller);
    }

    public static function controllerDescription(string $folder, string $controller): string
    {
        return PermissionLabels::controllerDescription($folder, $controller);
    }

    public static function methodLabel(string $method): string
    {
        return PermissionLabels::methodLabel($method);
    }
}
