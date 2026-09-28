<?php

namespace Mca\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Mca\Permission\Models\Permission;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\PackageAccessService;
use Mca\Permission\Services\PermissionService;

class McaPackagePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PackageAccessService $packages */
        $packages = app(PackageAccessService::class);
        $packages->syncDefinitions();

        /** @var PermissionService $permissions */
        $permissions = app(PermissionService::class);

        foreach ($this->roleMatrix() as $slug => $matrix) {
            $role = Role::query()->where('slug', $slug)->first();
            if (! $role || $role->is_root) {
                continue;
            }

            $ids = $this->resolvePermissionIds($packages, $matrix);
            if ($ids === []) {
                continue;
            }

            $merged = array_values(array_unique(array_merge(
                $permissions->permissionIdsForRole((int) $role->id),
                $ids,
            )));

            $permissions->syncRolePermissions((int) $role->id, $merged);
        }

        $permissions->flushCache();
    }

    /**
     * @return array<string, array{packages?: array<string, list<string>>|string, settings_groups?: list<string>|string}>
     */
    private function roleMatrix(): array
    {
        $custom = config('permission.seed.role_permissions');
        if (is_array($custom) && $custom !== []) {
            return $custom;
        }

        return [
            'admin' => [
                'packages' => '*',
                'settings_groups' => '*',
            ],
            'manager' => [
                'packages' => [
                    'settings' => ['view', 'manage'],
                    'address' => ['view', 'manage'],
                    'seo' => ['view', 'manage'],
                    'captcha' => ['view'],
                    'smtp' => ['view'],
                ],
                'settings_groups' => [
                    'company',
                    'general',
                    'locale',
                    'branding',
                    'contact',
                    'social',
                    'seo',
                    'maintenance',
                ],
            ],
            'editor' => [
                'packages' => [
                    'settings' => ['view'],
                    'seo' => ['view', 'manage'],
                ],
                'settings_groups' => [
                    'branding',
                    'contact',
                    'social',
                    'seo',
                ],
            ],
            'agent' => [
                'packages' => [],
                'settings_groups' => [],
            ],
        ];
    }

    /**
     * @param  array{packages?: array<string, list<string>>|string, settings_groups?: list<string>|string}  $matrix
     * @return list<int>
     */
    private function resolvePermissionIds(PackageAccessService $packages, array $matrix): array
    {
        $ids = [];
        $packageMap = $matrix['packages'] ?? [];

        if ($packageMap === '*') {
            foreach (config('permission.packages', []) as $package => $config) {
                if (! is_array($config) || ! empty($config['root_only'])) {
                    continue;
                }

                $ids = array_merge($ids, $this->idsForPackageAbilities(
                    $packages,
                    (string) $package,
                    ['view', 'manage'],
                ));
            }
        } elseif (is_array($packageMap)) {
            foreach ($packageMap as $package => $abilities) {
                $ids = array_merge($ids, $this->idsForPackageAbilities(
                    $packages,
                    (string) $package,
                    is_array($abilities) ? $abilities : ['view'],
                ));
            }
        }

        $groups = $matrix['settings_groups'] ?? [];
        if ($groups === '*') {
            $groups = array_keys((array) config('settings.definitions', []));
            if ($groups === []) {
                $groups = (array) config('settings.groups_order', []);
            }
        }

        if (is_array($groups)) {
            foreach ($groups as $group) {
                $permission = Permission::query()
                    ->where('folder', PackageAccessService::FOLDER)
                    ->where('controller', $packages->controllerFor('settings'))
                    ->where('method', 'group.'.$group)
                    ->first();

                if ($permission) {
                    $ids[] = (int) $permission->id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $abilities
     * @return list<int>
     */
    private function idsForPackageAbilities(PackageAccessService $packages, string $package, array $abilities): array
    {
        if (! empty(config('permission.packages.'.$package.'.root_only'))) {
            return [];
        }

        $controller = $packages->controllerFor($package);
        $ids = [];

        foreach ($abilities as $ability) {
            $permission = Permission::query()
                ->where('folder', PackageAccessService::FOLDER)
                ->where('controller', $controller)
                ->where('method', $ability)
                ->where('is_root_only', false)
                ->first();

            if ($permission) {
                $ids[] = (int) $permission->id;
            }
        }

        return $ids;
    }
}
