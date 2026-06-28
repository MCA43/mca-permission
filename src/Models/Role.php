<?php

namespace Mca\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'is_system',
        'is_root',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_root' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function users(): HasMany
    {
        $userModel = config('permission.user_model', \App\Models\User::class);
        $roleColumn = config('permission.user_role_column', 'role_id');

        return $this->hasMany($userModel, $roleColumn);
    }

    public function permissionsEditable(): bool
    {
        return ! $this->is_root;
    }
}
