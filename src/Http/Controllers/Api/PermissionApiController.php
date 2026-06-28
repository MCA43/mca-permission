<?php

namespace Mca\Permission\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mca\Permission\Models\Permission;
use Mca\Permission\Models\Role;
use Mca\Permission\Models\ScanSegment;
use Mca\Permission\Services\PermissionScannerService;
use Mca\Permission\Services\PermissionService;
use Mca\Permission\Services\ScanSegmentService;

class PermissionApiController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly PermissionScannerService $scanner,
        private readonly ScanSegmentService $scanSegments,
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

    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        $ids = $request->input('permission_ids', []);
        if (! is_array($ids)) {
            return response()->json(['message' => 'permission_ids dizisi gerekli'], 422);
        }

        $this->permissions->syncRolePermissions($role->id, $ids);

        return response()->json([
            'role_id' => $role->id,
            'permission_ids' => $this->permissions->permissionIdsForRole($role->id),
        ]);
    }

    public function indexScanSegments(): JsonResponse
    {
        return response()->json([
            'segments' => $this->scanSegments->allOrdered(),
        ]);
    }

    public function storeScanSegment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folder' => ['required', 'string', 'max:128'],
            'path' => ['required', 'string', 'max:255'],
            'namespace' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $segment = $this->scanSegments->create($data);

        return response()->json(['segment' => $segment], 201);
    }

    public function updateScanSegment(Request $request, ScanSegment $segment): JsonResponse
    {
        $data = $request->validate([
            'folder' => ['sometimes', 'string', 'max:128'],
            'path' => ['sometimes', 'string', 'max:255'],
            'namespace' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json([
            'segment' => $this->scanSegments->update($segment, $data),
        ]);
    }

    public function destroyScanSegment(ScanSegment $segment): JsonResponse
    {
        $this->scanSegments->delete($segment);

        return response()->json(['deleted' => true]);
    }

    public function syncScanSegmentsFromConfig(): JsonResponse
    {
        $this->scanSegments->ensureConfigSegments();

        return response()->json([
            'segments' => $this->scanSegments->allOrdered(),
        ]);
    }
}
