<?php

namespace Mca\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'is_root_only',
        'folder',
        'controller',
        'module',
        'module_description',
        'method',
        'method_description',
    ];

    protected function casts(): array
    {
        return [
            'is_root_only' => 'boolean',
        ];
    }

    /** @return list<string> */
    public function assignedRoles(): array
    {
        return DB::table('role_permission')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->where('role_permission.permission_id', $this->id)
            ->pluck('roles.name')
            ->all();
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeForRoute($query, string $folder, string $controller, string $method): void
    {
        $query->where('folder', $folder)
            ->where('controller', $controller)
            ->where(function ($q) use ($method) {
                $q->where('method', $method)->orWhere('method', '*');
            });
    }
}
