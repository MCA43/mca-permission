<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\StoreDepartmentRequest;
use Mca\Permission\Http\Requests\UpdateDepartmentRequest;
use Mca\Permission\Services\DepartmentService;
use Mca\Permission\Support\PermissionMode;

class DepartmentController extends McaPermissionController
{
    public function __construct(
        private readonly DepartmentService $departments,
    ) {}

    public function index(): View
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);

        $configured = $this->departments->modelConfigured();

        return $this->view('departments.index', [
            'departments' => $configured ? $this->departments->paginated(20) : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
            'departmentConfigured' => $configured,
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);
        abort_unless($this->departments->modelConfigured(), 404);

        $this->departments->create($request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'departments.index')
            ->with('mca_perm_status', mca_perm('flash.department_created'));
    }

    public function update(UpdateDepartmentRequest $request, int|string $department): RedirectResponse
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);

        $subject = $this->findDepartment($department);
        $this->departments->update($subject, $request->validated());

        $routeKey = config('permission.department.route_key', 'slug');

        return redirect()
            ->route(config('permission.routes.name_prefix').'departments.index')
            ->with('mca_perm_status', mca_perm('flash.department_updated'));
    }

    public function destroy(int|string $department): RedirectResponse
    {
        abort_unless(PermissionMode::supportsDepartmentGrants(), 404);

        $subject = $this->findDepartment($department);

        if (! $this->departments->delete($subject)) {
            return back()->withErrors(['department' => 'Departman silinemedi. Bağlı kullanıcı olabilir.']);
        }

        return redirect()
            ->route(config('permission.routes.name_prefix').'departments.index')
            ->with('mca_perm_status', mca_perm('flash.department_deleted'));
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
}
