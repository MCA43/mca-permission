<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\SyncRolePermissionsRequest;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\PermissionService;

class RolePermissionController extends McaPermissionController
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function edit(Role $role): View
    {
        return $this->view('roles.permissions', [
            'role' => $role,
            'roleLabel' => $role->name,
            'editable' => $role->permissionsEditable(),
            'groups' => $this->permissions->groupedEditablePermissions(),
            'assignedIds' => $this->permissions->permissionIdsForRole($role->id),
        ]);
    }

    public function update(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $ids = $request->input('permission_ids', []);
        $this->permissions->syncRolePermissions($role->id, is_array($ids) ? $ids : []);

        return redirect()
            ->route(config('permission.routes.name_prefix').'roles.permissions.edit', $role)
            ->with('mca_perm_status', mca_perm('flash.role_permissions_updated', ['name' => $role->name]));
    }
}
