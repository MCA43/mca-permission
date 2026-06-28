<?php

namespace Mca\Permission\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\GrantResolverRegistry;
use Mca\Permission\Services\PermissionService;
use Mca\Permission\Tests\Models\User;
use Mca\Permission\Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    private function service(): PermissionService
    {
        return new PermissionService(new GrantResolverRegistry);
    }

    public function test_build_permission_meta_generates_expected_name(): void
    {
        $meta = $this->service()->buildPermissionMeta('Panel', 'DashboardController', 'index');

        $this->assertSame('panelDashboard.index', $meta['name']);
        $this->assertSame('Panel', $meta['folder']);
        $this->assertSame('DashboardController', $meta['controller']);
    }

    public function test_normalize_controller_appends_suffix(): void
    {
        $this->assertSame('UsersController', $this->service()->normalizeController('Users'));
    }

    public function test_resolve_from_controller_class_uses_scan_segment_folder(): void
    {
        \Mca\Permission\Models\ScanSegment::query()->create([
            'folder' => 'DenemeModule',
            'path' => 'Modules/DenemeModule/Controllers',
            'namespace' => 'App\\Modules\\DenemeModule\\Controllers',
            'is_active' => true,
            'from_config' => false,
            'sort_order' => 30,
        ]);

        $resolved = $this->service()->resolveFromControllerClass(
            'App\\Modules\\DenemeModule\\Controllers\\MainController',
            'index',
        );

        $this->assertSame([
            'folder' => 'DenemeModule',
            'controller' => 'MainController',
            'method' => 'index',
        ], $resolved);
    }

    public function test_resolve_from_controller_class_prefers_longest_namespace_match(): void
    {
        \Mca\Permission\Models\ScanSegment::query()->create([
            'folder' => 'Modules',
            'path' => 'Modules',
            'namespace' => 'App\\Modules',
            'is_active' => true,
            'from_config' => false,
            'sort_order' => 10,
        ]);
        \Mca\Permission\Models\ScanSegment::query()->create([
            'folder' => 'DenemeModule',
            'path' => 'Modules/DenemeModule/Controllers',
            'namespace' => 'App\\Modules\\DenemeModule\\Controllers',
            'is_active' => true,
            'from_config' => false,
            'sort_order' => 20,
        ]);

        $resolved = $this->service()->resolveFromControllerClass(
            'App\\Modules\\DenemeModule\\Controllers\\MainController',
            'index',
        );

        $this->assertSame('DenemeModule', $resolved['folder']);
    }

    public function test_sync_user_exclusive_persists(): void
    {
        $roleId = $this->seedEditorRole();
        $user = User::query()->create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role_id' => $roleId,
        ]);

        $saved = $this->service()->syncUserPermissions($user->id, [], true);

        $this->assertTrue($saved);
        $this->assertTrue($user->fresh()->mca_permission_exclusive);
    }

    public function test_user_permission_matrix_includes_role_permissions(): void
    {
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'panelDashboard.index',
            'is_root_only' => false,
            'folder' => 'Panel',
            'controller' => 'DashboardController',
            'module' => 'panelDashboard',
            'method' => 'index',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminRoleId = Role::query()->create([
            'slug' => 'admin',
            'name' => 'Yönetici',
            'is_active' => true,
            'is_system' => true,
            'is_root' => false,
            'sort_order' => 10,
        ])->id;

        DB::table('role_permission')->insert([
            'role_id' => $adminRoleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role_id' => $adminRoleId,
        ]);

        $matrix = $this->service()->userPermissionMatrix($user);

        $this->assertContains((int) $permissionId, $matrix['roleIds']);
        $this->assertSame('Yönetici', $matrix['roleLabel']);
        $this->assertFalse($matrix['exclusive']);
    }

    public function test_permission_ids_for_role_display_includes_all_for_root(): void
    {
        $rootRoleId = Role::query()->create([
            'slug' => 'root',
            'name' => 'Root',
            'is_active' => true,
            'is_system' => true,
            'is_root' => true,
            'sort_order' => 0,
        ])->id;

        DB::table('permissions')->insert([
            [
                'name' => 'panelDashboard.index',
                'is_root_only' => false,
                'folder' => 'Panel',
                'controller' => 'DashboardController',
                'module' => 'panelDashboard',
                'method' => 'index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'panelSecret.index',
                'is_root_only' => true,
                'folder' => 'Panel',
                'controller' => 'SecretController',
                'module' => 'panelSecret',
                'method' => 'index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $ids = $this->service()->permissionIdsForRoleDisplay($rootRoleId);

        $this->assertCount(1, $ids);
    }

    public function test_create_update_and_delete_manual_permission(): void
    {
        $permission = $this->service()->createFromInput([
            'folder' => 'Panel',
            'controller' => 'ReportsController',
            'method' => 'export',
            'module_description' => 'Raporlar',
            'is_root_only' => false,
        ]);

        $this->assertSame('panelReports.export', $permission->name);

        $updated = $this->service()->updatePermission($permission, [
            'folder' => 'Panel',
            'controller' => 'ReportsController',
            'method' => 'export',
            'module_description' => 'Rapor dışa aktarma',
            'method_description' => 'Excel indir',
            'is_root_only' => true,
        ]);

        $this->assertTrue($updated->is_root_only);
        $this->assertSame('Excel indir', $updated->method_description);

        $this->assertTrue($this->service()->deletePermission($updated));
        $this->assertNull(\Mca\Permission\Models\Permission::query()->find($updated->id));
    }
}
