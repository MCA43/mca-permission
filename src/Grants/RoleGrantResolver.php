<?php

namespace Mca\Permission\Grants;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Mca\Permission\Contracts\GrantResolver;
use Mca\Permission\Models\Permission;

class RoleGrantResolver implements GrantResolver
{
    public function grants(Authenticatable $user, Permission $permission): bool
    {
        $column = (string) config('permission.user_role_column', 'role');
        $roleSlug = isset($user->{$column}) ? (string) $user->{$column} : '';

        if ($roleSlug === '') {
            return false;
        }

        return DB::table('role_permission')
            ->where('permission_id', $permission->id)
            ->where('role', $roleSlug)
            ->exists();
    }
}
