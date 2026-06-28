<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\StoreRoleRequest;
use Mca\Permission\Http\Requests\UpdateRoleRequest;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\RoleService;

class RoleController extends McaPermissionController
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(): View
    {
        return $this->view('roles.index', [
            'roles' => $this->roles->allWithUserCounts(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'roles.index')
            ->with('mca_perm_status', mca_perm('flash.role_created'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->is_system || $role->is_root) {
            abort(403);
        }

        $this->roles->update($role, $request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'roles.index')
            ->with('mca_perm_status', mca_perm('flash.role_updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (! $this->roles->delete($role)) {
            return back()->withErrors(['role' => 'Rol silinemedi. Sistem rolü veya atanmış kullanıcı olabilir.']);
        }

        return redirect()
            ->route(config('permission.routes.name_prefix').'roles.index')
            ->with('mca_perm_status', mca_perm('flash.role_deleted'));
    }
}
