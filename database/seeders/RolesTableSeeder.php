<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'customer', 'permissions' => null],
            ['id' => 2, 'name' => 'agent',    'permissions' => null],
            ['id' => 3, 'name' => 'admin',    'permissions' => null],
        ]);
    }
}
