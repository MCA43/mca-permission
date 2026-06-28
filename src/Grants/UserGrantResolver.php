<?php

namespace Mca\Permission\Grants;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mca\Permission\Contracts\GrantResolver;
use Mca\Permission\Models\Permission;
use Mca\Permission\Support\PermissionMode;

class UserGrantResolver implements GrantResolver
{
    public function grants(Authenticatable $user, Permission $permission): bool
    {
        if (! PermissionMode::supportsUserGrants()) {
            return false;
        }

        if (! Schema::hasTable('user_permission')) {
            return false;
        }

        $userId = $user->getAuthIdentifier();
        if ($userId === null) {
            return false;
        }

        return DB::table('user_permission')
            ->where('user_id', $userId)
            ->where('permission_id', $permission->id)
            ->exists();
    }
}
