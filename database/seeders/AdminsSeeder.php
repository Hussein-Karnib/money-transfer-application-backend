<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminsSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();

        if (! $adminUser) {
            return;
        }

        Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            ['privilege_level' => 1]
        );
    }
}
