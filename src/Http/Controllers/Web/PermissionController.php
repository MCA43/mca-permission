<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\StorePermissionRequest;
use Mca\Permission\Http\Requests\UpdatePermissionRequest;
use Mca\Permission\Models\Permission;
use Mca\Permission\Services\PermissionService;

class PermissionController extends McaPermissionController
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function index(): View
    {
        return $this->view('permissions.index', [
            'permissions' => Permission::query()
                ->orderBy('module')
                ->orderBy('method')
                ->get(),
        ]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $this->permissions->createFromInput($request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'index')
            ->with('mca_perm_status', mca_perm('flash.permission_created'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissions->updatePermission($permission, $request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'index')
            ->with('mca_perm_status', mca_perm('flash.permission_updated'));
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->permissions->deletePermission($permission);

        return redirect()
            ->route(config('permission.routes.name_prefix').'index')
            ->with('mca_perm_status', mca_perm('flash.permission_deleted'));
    }
}
