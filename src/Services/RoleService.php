<?php

namespace Mca\Permission\Services;

use Illuminate\Support\Str;
use Mca\Permission\Models\Role;

class RoleService
{
    public function allWithUserCounts()
    {
        $userModel = config('permission.user_model');
        $roleColumn = config('permission.user_role_column', 'role');

        return Role::query()
            ->withCount([
                'users as users_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Role
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        return Role::query()->create([
            'slug' => $slug,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_system' => false,
            'is_root' => false,
            'sort_order' => (int) Role::query()->max('sort_order') + 10,
        ]);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $role->is_active),
        ]);

        return $role->fresh();
    }

    public function delete(Role $role): bool
    {
        if ($role->is_system || $role->is_root) {
            return false;
        }

        $userModel = config('permission.user_model');
        $roleColumn = config('permission.user_role_column', 'role');
        $inUse = $userModel::query()->where($roleColumn, $role->slug)->exists();

        if ($inUse) {
            return false;
        }

        return (bool) $role->delete();
    }
}
