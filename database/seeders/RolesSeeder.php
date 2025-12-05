<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'admin', 'permissions' => null],
            ['id' => 2, 'name' => 'agent', 'permissions' => null],
            ['id' => 3, 'name' => 'customer', 'permissions' => null],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'permissions' => $role['permissions'],
                ]
            );
        }
    }
}
