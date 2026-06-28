<?php

namespace Mca\Permission\Grants;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mca\Permission\Contracts\GrantResolver;
use Mca\Permission\Models\Permission;
use Mca\Permission\Support\PermissionMode;

class DepartmentGrantResolver implements GrantResolver
{
    public function grants(Authenticatable $user, Permission $permission): bool
    {
        if (! PermissionMode::supportsDepartmentGrants()) {
            return false;
        }

        if (! Schema::hasTable('department_permission')) {
            return false;
        }

        $column = (string) config('permission.department.user_column', 'department_id');
        if (! isset($user->{$column}) || $user->{$column} === null) {
            return false;
        }

        return DB::table('department_permission')
            ->where('department_id', $user->{$column})
            ->where('permission_id', $permission->id)
            ->exists();
    }
}
