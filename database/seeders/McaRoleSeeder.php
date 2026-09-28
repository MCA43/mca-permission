<?php

namespace Mca\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Mca\Permission\Models\Role;

class McaRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roles() as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role + ['is_active' => true],
            );
        }
    }

    /**
     * @return list<array{
     *   slug: string,
     *   name: string,
     *   description: string,
     *   is_system: bool,
     *   is_root: bool,
     *   sort_order: int
     * }>
     */
    public function roles(): array
    {
        $custom = config('permission.seed.roles');

        if (is_array($custom) && $custom !== []) {
            return array_values($custom);
        }

        return [
            [
                'slug' => 'root',
                'name' => 'Root',
                'description' => 'Sistem kök yöneticisi — tüm yetkiler, MCA Hub ve izin yönetimi',
                'is_system' => true,
                'is_root' => true,
                'sort_order' => 0,
            ],
            [
                'slug' => 'admin',
                'name' => 'Yönetici',
                'description' => 'Ofis genel yöneticisi — MCA paketleri ve ayarlar (Hub / yetki yönetimi hariç)',
                'is_system' => true,
                'is_root' => false,
                'sort_order' => 10,
            ],
            [
                'slug' => 'manager',
                'name' => 'Ofis müdürü',
                'description' => 'Şube / ofis yönetimi — firma ayarları, adres, SEO; güvenlik altyapısı yok',
                'is_system' => true,
                'is_root' => false,
                'sort_order' => 20,
            ],
            [
                'slug' => 'editor',
                'name' => 'Editör',
                'description' => 'İçerik ve marka — SEO, branding / iletişim ayarları',
                'is_system' => true,
                'is_root' => false,
                'sort_order' => 30,
            ],
            [
                'slug' => 'agent',
                'name' => 'Danışman',
                'description' => 'Saha danışmanı — panel operasyon; MCA paket erişimi yok',
                'is_system' => true,
                'is_root' => false,
                'sort_order' => 40,
            ],
        ];
    }
}
