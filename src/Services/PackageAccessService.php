<?php

namespace Mca\Permission\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Mca\Permission\Models\Permission;

class PackageAccessService
{
    public const FOLDER = 'Mca';

    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function isRootOnly(string $package): bool
    {
        $config = $this->packageConfig($package);

        return (bool) ($config['root_only'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function packageConfig(string $package): array
    {
        $packages = config('permission.packages', []);

        return is_array($packages[$package] ?? null) ? $packages[$package] : [];
    }

    public function controllerFor(string $package): string
    {
        $custom = (string) ($this->packageConfig($package)['controller'] ?? '');
        if ($custom !== '') {
            return $this->permissions->normalizeController($custom);
        }

        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $package)));

        return $this->permissions->normalizeController($studly.'Package');
    }

    public function allows(?Authenticatable $user, string $package, string $ability = 'view'): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->permissions->isRoot($user)) {
            return true;
        }

        if ($this->isRootOnly($package)) {
            return false;
        }

        $config = $this->packageConfig($package);
        if ($config === []) {
            return false;
        }

        $ability = $this->normalizeAbility($ability);

        return $this->permissions->canAccess(
            $user,
            self::FOLDER,
            $this->controllerFor($package),
            $ability,
        );
    }

    public function allowsSettingsGroup(?Authenticatable $user, string $group): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->permissions->isRoot($user)) {
            return true;
        }

        if ($this->allows($user, 'settings', 'manage')) {
            return true;
        }

        if (! $this->allows($user, 'settings', 'view')) {
            return false;
        }

        return $this->permissions->canAccess(
            $user,
            self::FOLDER,
            $this->controllerFor('settings'),
            'group.'.$group,
        );
    }

    /**
     * @param  list<string>  $groups
     * @return list<string>
     */
    public function filterSettingsGroups(?Authenticatable $user, array $groups): array
    {
        if (! $user) {
            return [];
        }

        if ($this->permissions->isRoot($user) || $this->allows($user, 'settings', 'manage')) {
            return array_values($groups);
        }

        return array_values(array_filter(
            $groups,
            fn (string $group) => $this->allowsSettingsGroup($user, $group),
        ));
    }

    public function abilityForRequest(Request $request): string
    {
        return $request->isMethodSafe() ? 'view' : 'manage';
    }

    /**
     * Ensure permission rows exist for configured MCA packages.
     *
     * @return array{created: int, updated: int}
     */
    public function syncDefinitions(): array
    {
        $created = 0;
        $updated = 0;

        foreach (config('permission.packages', []) as $package => $config) {
            if (! is_array($config)) {
                continue;
            }

            $package = (string) $package;
            $rootOnly = (bool) ($config['root_only'] ?? false);
            $label = (string) ($config['label'] ?? ucfirst($package));
            $controller = $this->controllerFor($package);
            $abilities = $rootOnly
                ? ['view']
                : array_values(array_unique(array_merge(
                    ['view', 'manage'],
                    is_array($config['abilities'] ?? null) ? $config['abilities'] : [],
                )));

            foreach ($abilities as $ability) {
                $ability = $this->normalizeAbility((string) $ability);
                $result = $this->upsertPermission(
                    $controller,
                    $ability,
                    $label,
                    $this->abilityLabel($ability),
                    $rootOnly,
                );
                $created += $result['created'];
                $updated += $result['updated'];
            }

            if (! empty($config['groups']) && ! $rootOnly) {
                foreach ($this->settingsGroups() as $group) {
                    $result = $this->upsertPermission(
                        $controller,
                        'group.'.$group,
                        $label,
                        $this->settingsGroupLabel($group),
                        false,
                    );
                    $created += $result['created'];
                    $updated += $result['updated'];
                }
            }
        }

        if ($created > 0 || $updated > 0) {
            $this->permissions->flushCache();
        }

        return compact('created', 'updated');
    }

    /**
     * @return array{created: int, updated: int}
     */
    private function upsertPermission(
        string $controller,
        string $method,
        string $moduleDescription,
        string $methodDescription,
        bool $rootOnly,
    ): array {
        $meta = $this->permissions->buildPermissionMeta(
            self::FOLDER,
            $controller,
            $method,
            $moduleDescription,
        );

        $existing = Permission::query()
            ->where('folder', $meta['folder'])
            ->where('controller', $meta['controller'])
            ->where('method', $meta['method'])
            ->first();

        if (! $existing) {
            Permission::query()->create([
                'name' => $meta['name'],
                'folder' => $meta['folder'],
                'controller' => $meta['controller'],
                'module' => $meta['module'],
                'method' => $meta['method'],
                'module_description' => $moduleDescription,
                'method_description' => $methodDescription,
                'is_root_only' => $rootOnly,
            ]);

            return ['created' => 1, 'updated' => 0];
        }

        $dirty = false;
        foreach ([
            'name' => $meta['name'],
            'module' => $meta['module'],
            'module_description' => $moduleDescription,
            'method_description' => $methodDescription,
            'is_root_only' => $rootOnly,
        ] as $key => $value) {
            if ($existing->{$key} !== $value) {
                $existing->{$key} = $value;
                $dirty = true;
            }
        }

        if ($dirty) {
            $existing->save();

            return ['created' => 0, 'updated' => 1];
        }

        return ['created' => 0, 'updated' => 0];
    }

    /** @return list<string> */
    private function settingsGroups(): array
    {
        $order = config('settings.groups_order', []);
        if (is_array($order) && $order !== []) {
            return array_values(array_map('strval', $order));
        }

        $definitions = config('settings.definitions', []);

        return is_array($definitions) ? array_values(array_map('strval', array_keys($definitions))) : [];
    }

    private function settingsGroupLabel(string $group): string
    {
        $key = 'mca-settings::settings.groups.'.$group;
        $label = __($key);

        if (is_string($label) && $label !== $key) {
            return 'Grup: '.$label;
        }

        return 'Grup: '.$group;
    }

    private function abilityLabel(string $ability): string
    {
        return match ($ability) {
            'view' => 'Görüntüleme',
            'manage' => 'Yönetim',
            default => $ability,
        };
    }

    private function normalizeAbility(string $ability): string
    {
        $ability = trim($ability);

        return $ability !== '' ? $ability : 'view';
    }
}
