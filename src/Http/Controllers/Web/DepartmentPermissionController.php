<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\SyncDepartmentPermissionsRequest;
use Mca\Permission\Services\DepartmentService;
use Mca\Permission\Services\PermissionService;
use Mca\Permission\Support\PermissionGrantContext;
use Mca\Permission\Support\PermissionMode;

class DepartmentPermissionController extends McaPermissionController
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly DepartmentService $departments,
    ) {}

    public function edit(int|string $department): View
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);

        $subject = $this->findDepartment($department);
        $fresh = $subject->fresh() ?? $subject;

        return $this->view('departments.permissions', [
            'subject' => $fresh,
            'subjectLabel' => $this->departmentLabel($fresh),
            'groups' => $this->permissions->groupedEditablePermissions(),
            'assignedIds' => $this->permissions->permissionIdsForDepartment($fresh->getKey()),
            'exclusive' => PermissionGrantContext::isDepartmentExclusive($fresh),
        ]);
    }

    public function update(SyncDepartmentPermissionsRequest $request, int|string $department): RedirectResponse
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);

        $subject = $this->findDepartment($department);
        $ids = $request->input('permission_ids', []);
        $exclusive = $request->has('permission_exclusive') && $request->boolean('permission_exclusive');
        $saved = $this->permissions->syncDepartmentPermissions(
            $subject->getKey(),
            is_array($ids) ? $ids : [],
            $exclusive,
        );

        if (! $saved) {
            return back()
                ->withInput()
                ->withErrors(['permission_exclusive' => mca_perm('errors.exclusive_save')]);
        }

        $routeKey = config('permission.department.route_key', 'id');

        return redirect()
            ->route(config('permission.routes.name_prefix').'departments.permissions.edit', [
                'department' => $subject->{$routeKey} ?? $subject->getKey(),
            ])
            ->with('mca_perm_status', mca_perm('flash.department_permissions_updated', [
                'name' => $this->departmentLabel($subject),
            ]));
    }

    private function findDepartment(int|string $id): object
    {
        abort_unless($this->departments->modelConfigured(), 404);

        $model = config('permission.department.model');
        $routeKey = config('permission.department.route_key', 'id');

        if ($routeKey === 'slug') {
            return $model::query()->where('slug', $id)->firstOrFail();
        }

        return $model::query()->findOrFail($id);
    }

    private function departmentLabel(object $department): string
    {
        return (string) ($department->name ?? $department->slug ?? $department->getKey());
    }
}
