<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\StoreUserRequest;
use Mca\Permission\Http\Requests\UpdateUserRequest;
use Mca\Permission\Models\Role;
use Mca\Permission\Services\DepartmentService;
use Mca\Permission\Services\UserService;
use Mca\Permission\Support\PermissionMode;

class UserController extends McaPermissionController
{
    public function __construct(
        private readonly UserService $users,
        private readonly DepartmentService $departments,
    ) {}

    public function index(): View
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $roleColumn = config('permission.user_role_column', 'role_id');

        return $this->view('users.index', [
            'users' => $this->users->paginated(20),
            'roles' => $this->users->assignableRoles(),
            'roleNames' => Role::query()->pluck('name', 'id'),
            'roleColumn' => $roleColumn,
            'showDepartment' => $this->users->userHasDepartmentColumn(),
            'departments' => $this->users->userHasDepartmentColumn()
                ? $this->departments->allForSelect()
                : collect(),
            'departmentNames' => $this->users->userHasDepartmentColumn()
                ? $this->departments->nameMap()
                : collect(),
            'deptColumn' => config('permission.department.user_column', 'department_id'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $this->users->create($request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'users.index')
            ->with('mca_perm_status', mca_perm('flash.user_created'));
    }

    public function update(UpdateUserRequest $request, int|string $user): RedirectResponse
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $subject = $this->findUser($user);
        $this->users->update($subject, $request->validated());

        return redirect()
            ->route(config('permission.routes.name_prefix').'users.index')
            ->with('mca_perm_status', mca_perm('flash.user_updated'));
    }

    public function destroy(int|string $user): RedirectResponse
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $subject = $this->findUser($user);

        if (! $this->users->delete($subject, Auth::user())) {
            return back()->withErrors(['user' => mca_perm('errors.user_delete')]);
        }

        return redirect()
            ->route(config('permission.routes.name_prefix').'users.index')
            ->with('mca_perm_status', mca_perm('flash.user_deleted'));
    }

    private function findUser(int|string $userId): object
    {
        $userModel = config('permission.user_model');

        return $userModel::query()->findOrFail($userId);
    }
}
