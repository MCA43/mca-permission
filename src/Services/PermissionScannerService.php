<?php

namespace Mca\Permission\Services;

use Illuminate\Support\Facades\File;
use Mca\Permission\Models\Permission;
use ReflectionClass;
use ReflectionMethod;

class PermissionScannerService
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    /**
     * @return array{missing_permissions: list<array<string, mixed>>, existing_permissions: list<array<string, mixed>>, total_missing: int, total_existing: int}
     */
    public function scan(): array
    {
        $existing = Permission::query()->get()->keyBy('name');
        $missing = [];
        $found = [];
        $seenMissing = [];
        $seenExisting = [];

        foreach ($this->discoverControllers() as $ctrl) {
            foreach ($this->methodsForClass($ctrl['class']) as $method) {
                $meta = $this->permissions->buildPermissionMeta(
                    $ctrl['folder'],
                    $ctrl['controller'],
                    $method['method'],
                );
                $meta['is_root_only'] = $this->isRootOnlyController($ctrl['folder'], $ctrl['controller']);
                $name = $meta['name'];

                if ($existing->has($name)) {
                    if (isset($seenExisting[$name])) {
                        continue;
                    }
                    $seenExisting[$name] = true;
                    $row = $existing[$name];
                    $found[] = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'folder' => $row->folder,
                        'controller' => $row->controller,
                        'method' => $row->method,
                        'is_root_only' => $row->is_root_only,
                        'assigned_roles' => $row->assignedRoles(),
                    ];
                } else {
                    if (isset($seenMissing[$name])) {
                        continue;
                    }
                    $seenMissing[$name] = true;
                    $missing[] = $meta + ['is_root_only' => $meta['is_root_only']];
                }
            }
        }

        return [
            'missing_permissions' => $missing,
            'existing_permissions' => $found,
            'total_missing' => count($missing),
            'total_existing' => count($found),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{added: int, errors: list<array<string, string>>}
     */
    public function addPermissions(array $items): array
    {
        $added = 0;
        $errors = [];

        foreach ($items as $row) {
            try {
                $folder = (string) ($row['folder'] ?? 'Panel');
                $controller = $this->permissions->normalizeController((string) ($row['controller'] ?? ''));
                $method = (string) ($row['method'] ?? 'index');
                $meta = $this->permissions->buildPermissionMeta($folder, $controller, $method);
                Permission::query()->updateOrCreate(
                    ['name' => $row['name'] ?? $meta['name']],
                    [
                        'folder' => $folder,
                        'controller' => $controller,
                        'module' => $row['module'] ?? $meta['module'],
                        'method' => $method,
                        'module_description' => $meta['module_description'],
                        'method_description' => $meta['method_description'],
                        'is_root_only' => (bool) ($row['is_root_only'] ?? $this->isRootOnlyController($folder, $controller)),
                    ],
                );
                $added++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'permission' => (string) ($row['name'] ?? '?'),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->permissions->flushCache();

        return ['added' => $added, 'errors' => $errors];
    }

    /**
     * @return array{added: int, labels_updated: int, errors: list<array<string, string>>, scan: array<string, mixed>}
     */
    public function syncAll(): array
    {
        $scan = $this->scan();
        $add = $this->addPermissions($scan['missing_permissions']);
        $labels = $this->permissions->syncPermissionLabels();

        return [
            'added' => $add['added'],
            'labels_updated' => $labels,
            'errors' => $add['errors'],
            'scan' => $scan,
        ];
    }

    /** @return list<array{class: string, folder: string, controller: string}> */
    private function discoverControllers(): array
    {
        $out = [];
        $baseController = config('permission.scan.base_controller');
        $segments = config('permission.scan.segments', []);

        foreach ($segments as $segment) {
            $path = app_path($segment['path'] ?? '');
            $folder = $segment['folder'] ?? 'Panel';
            $namespace = rtrim($segment['namespace'] ?? '', '\\');

            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $relative = str_replace(['/', '\\'], '\\', $file->getRelativePathname());
                $class = $namespace.'\\'.str_replace('.php', '', $relative);
                if (! class_exists($class)) {
                    continue;
                }
                $ref = new ReflectionClass($class);
                if ($ref->isAbstract()) {
                    continue;
                }
                if ($baseController && ! $ref->isSubclassOf($baseController)) {
                    continue;
                }
                $out[] = [
                    'class' => $class,
                    'folder' => $folder,
                    'controller' => $ref->getShortName(),
                ];
            }
        }

        return $out;
    }

    /** @return list<array{method: string}> */
    private function methodsForClass(string $className): array
    {
        $methods = [];
        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            $methods[] = ['method' => $method->getName()];
        }

        return $methods;
    }

    private function isRootOnlyController(string $folder, string $controller): bool
    {
        $controller = $this->permissions->normalizeController($controller);

        return in_array($controller, config('permission.root_only_controllers', []), true);
    }
}
