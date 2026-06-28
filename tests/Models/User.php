<?php

namespace Mca\Permission\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'mca_permission_exclusive',
    ];

    protected $casts = [
        'mca_permission_exclusive' => 'boolean',
    ];
}
