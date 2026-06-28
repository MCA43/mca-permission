<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Http\Requests\SyncUserPermissionsRequest;
use Mca\Permission\Services\PermissionService;
use Mca\Permission\Support\PermissionMode;

class UserPermissionController extends McaPermissionController
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function edit(int|string $user): View
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $subject = $this->findUser($user);

        return $this->view('users.permissions', [
            'subject' => $subject,
            'subjectLabel' => $subject->name ?? $subject->email,
            'groups' => $this->permissions->groupedEditablePermissions(),
            'matrix' => $this->permissions->userPermissionMatrix(
                $subject->fresh() ?? $subject
            ),
        ]);
    }

    public function update(SyncUserPermissionsRequest $request, int|string $user): RedirectResponse
    {
        abort_unless(PermissionMode::supportsUserGrants(), 404);

        $subject = $this->findUser($user);
        $ids = $request->input('permission_ids', []);
        $exclusive = $request->has('permission_exclusive') && $request->boolean('permission_exclusive');
        $saved = $this->permissions->syncUserPermissions(
            $subject->getAuthIdentifier(),
            is_array($ids) ? $ids : [],
            $exclusive,
        );

        if (! $saved) {
            return back()
                ->withInput()
                ->withErrors(['permission_exclusive' => mca_perm('errors.exclusive_save')]);
        }

        return redirect()
            ->route(config('permission.routes.name_prefix').'users.permissions.edit', $subject->getAuthIdentifier())
            ->with('mca_perm_status', mca_perm('flash.user_permissions_updated', [
                'name' => $subject->name ?? mca_perm('users.default_name'),
            ]));
    }

    private function findUser(int|string $userId): object
    {
        $userModel = config('permission.user_model');

        return $userModel::query()->findOrFail($userId);
    }
}
