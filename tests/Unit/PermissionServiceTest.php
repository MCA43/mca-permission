<?php

namespace Mca\Permission\Tests\Unit;

use Illuminate\Support\Facades\DB;
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

    public function test_sync_user_exclusive_persists(): void
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'role' => 'editor',
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

        DB::table('roles')->insert([
            'slug' => 'admin',
            'name' => 'Yönetici',
            'is_active' => true,
            'is_system' => true,
            'is_root' => false,
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permission')->insert([
            'role' => 'admin',
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $matrix = $this->service()->userPermissionMatrix($user);

        $this->assertContains((int) $permissionId, $matrix['roleIds']);
        $this->assertSame('Yönetici', $matrix['roleLabel']);
        $this->assertFalse($matrix['exclusive']);
    }

    public function test_permission_ids_for_role_display_includes_all_for_root(): void
    {
        DB::table('roles')->insert([
            'slug' => 'root',
            'name' => 'Root',
            'is_active' => true,
            'is_system' => true,
            'is_root' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $ids = $this->service()->permissionIdsForRoleDisplay('root');

        $this->assertCount(1, $ids);
    }
}
