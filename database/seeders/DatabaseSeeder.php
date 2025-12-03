<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Seed roles (our new naming)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $agentRole = Role::firstOrCreate(['name' => 'Agent']);
        $userRole  = Role::firstOrCreate(['name' => 'User']);

        // 2) Seed main admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Main Admin',
                'password' => Hash::make('Admin123!'), // change later
                'role_id'  => $adminRole->id,
            ]
        );

        // 3) Other seeders you actually need
        $this->call([
            CurrencySeeder::class,
            CountrySeeder::class,
            TransferMethodSeeder::class,
        ]);

    }
    
}
