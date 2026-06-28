<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Full mod için örnek departman modeli.
 * Publish: php artisan vendor:publish --tag=mca-permission-departments-stub
 */
class Department extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            \Mca\Permission\Models\Permission::class,
            'department_permission',
            'department_id',
            'permission_id',
        );
    }
}
