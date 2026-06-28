<?php

namespace Mca\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Mca\Permission\Models\Role;

class McaRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'root',
                'name' => 'Root',
                'description' => 'Tam yetki',
                'is_system' => true,
                'is_root' => true,
                'sort_order' => 0,
            ],
            [
                'slug' => 'admin',
                'name' => 'Yönetici',
                'description' => 'Genel yönetim',
                'is_system' => true,
                'is_root' => false,
                'sort_order' => 10,
            ],
            [
                'slug' => 'editor',
                'name' => 'Editör',
                'description' => 'İçerik yönetimi',
                'is_system' => false,
                'is_root' => false,
                'sort_order' => 20,
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role + ['is_active' => true],
            );
        }
    }
}
