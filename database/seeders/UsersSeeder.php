<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = '$2y$12$bU4X9yurCkXkdKRfY0fxkOkk5Fd5n/XdczIMYRZmT130T3aeBvu6O';

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Main Admin',
                'password' => $adminPassword,
                'role_id' => 1,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'jawadmcha@gmail.com'],
            [
                'name' => 'jawad chahine',
                'password' => '$2y$12$XAAaSDOi8FfmmL3iN6g8ce22vpfF5MIK5TbKG/bM2XFJCpqB0KuES',
                'role_id' => 3,
                'status' => 'pending',
                'email_verified_at' => now(),
            ]
        );
    }
}
