<?php

namespace Mca\Permission\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'mca_permission_exclusive',
    ];

    protected $casts = [
        'mca_permission_exclusive' => 'boolean',
    ];
}
