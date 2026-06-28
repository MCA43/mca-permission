<?php

namespace Mca\Permission\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Mca\Permission\Models\Permission;

interface GrantResolver
{
    public function grants(Authenticatable $user, Permission $permission): bool;
}
