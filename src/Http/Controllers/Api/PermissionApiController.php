<?php

namespace Mca\Permission\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mca\Permission\Models\Permission;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\PermissionScannerService;
use Mca\Permission\Services\PermissionService;

class PermissionApiController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly PermissionScannerService $scanner,
    ) {}

    public function scan(): JsonResponse
    {
        return response()->json($this->scanner->scan());
    }

    public function storeBulk(Request $request): JsonResponse
    {
        $items = $request->input('permissions', []);
        if (! is_array($items)) {
            return response()->json(['message' => 'permissions dizisi gerekli'], 422);
        }

        return response()->json($this->scanner->addPermissions($items));
    }

    public function syncLabels(): JsonResponse
    {
        return response()->json([
            'labels_updated' => $this->permissions->syncPermissionLabels(),
        ]);
    }

    public function syncAll(): JsonResponse
    {
        return response()->json($this->scanner->syncAll());
    }

    public function indexPermissions(): JsonResponse
    {
        return response()->json([
            'permissions' => Permission::query()->orderBy('name')->get(),
            'grouped' => $this->permissions->groupedEditablePermissions(),
        ]);
    }

    public function indexRoles(): JsonResponse
    {
        return response()->json(Role::query()->orderBy('sort_order')->get());
    }

    public function updateRolePermissions(Request $request, string $role): JsonResponse
    {
        $ids = $request->input('permission_ids', []);
        if (! is_array($ids)) {
            return response()->json(['message' => 'permission_ids dizisi gerekli'], 422);
        }

        $this->permissions->syncRolePermissions($role, $ids);

        return response()->json([
            'role' => $role,
            'permission_ids' => $this->permissions->permissionIdsForRole($role),
        ]);
    }
}
