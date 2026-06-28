<?php

namespace Mca\Permission\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mca\Permission\Models\Role;

class UserService
{
    public function paginated(int $perPage = 20)
    {
        $userModel = config('permission.user_model');
        $roleColumn = config('permission.user_role_column', 'role');
        $columns = ['id', 'name', 'email', $roleColumn];

        $deptColumn = config('permission.department.user_column', 'department_id');
        if ($this->userHasDepartmentColumn()) {
            $columns[] = $deptColumn;
        }

        return $userModel::query()
            ->select($columns)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Model
    {
        $userModel = config('permission.user_model');
        $roleColumn = config('permission.user_role_column', 'role');

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            $roleColumn => $data[$roleColumn] ?? config('permission.user.default_role', 'editor'),
        ];

        if (isset($data['is_active'])) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if ($this->userHasDepartmentColumn() && array_key_exists('department_id', $data)) {
            $payload[config('permission.department.user_column', 'department_id')] = $data['department_id'] ?: null;
        }

        return $userModel::query()->create($payload);
    }

    public function update(Model $user, array $data): Model
    {
        $roleColumn = config('permission.user_role_column', 'role');

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            $roleColumn => $data[$roleColumn],
        ];

        if (isset($data['is_active'])) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if ($this->userHasDepartmentColumn() && array_key_exists('department_id', $data)) {
            $payload[config('permission.department.user_column', 'department_id')] = $data['department_id'] ?: null;
        }

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return $user->fresh();
    }

    public function delete(Model $user, ?Authenticatable $actor = null): bool
    {
        if ($actor && (int) $user->getKey() === (int) $actor->getAuthIdentifier()) {
            return false;
        }

        $roleColumn = config('permission.user_role_column', 'role');
        if (($user->{$roleColumn} ?? '') === 'root') {
            return false;
        }

        return (bool) $user->delete();
    }

    /** @return list<array{slug: string, name: string}> */
    public function assignableRoles(): array
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'name', 'is_root'])
            ->reject(fn (Role $r) => $r->is_root)
            ->values()
            ->all();
    }

    public function userHasDepartmentColumn(): bool
    {
        if (! \Mca\Permission\Support\PermissionMode::supportsDepartmentGrants()) {
            return false;
        }

        $userModel = config('permission.user_model');
        $table = (new $userModel)->getTable();
        $column = config('permission.department.user_column', 'department_id');

        return Schema::hasColumn($table, $column);
    }
}
