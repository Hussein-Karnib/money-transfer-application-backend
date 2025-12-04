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
        // 1) Seed roles (matching IMPORTANT.txt format: lowercase names with specific IDs)
        // First, ensure roles exist with correct IDs and names
        $adminRole = Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'permissions' => null]
        );
        if ($adminRole->name !== 'admin') {
            $adminRole->update(['name' => 'admin']);
        }
        
        $agentRole = Role::firstOrCreate(
            ['id' => 2],
            ['name' => 'agent', 'permissions' => null]
        );
        if ($agentRole->name !== 'agent') {
            $agentRole->update(['name' => 'agent']);
        }
        
        $userRole = Role::firstOrCreate(
            ['id' => 3],
            ['name' => 'customer', 'permissions' => null]
        );
        if ($userRole->name !== 'customer') {
            $userRole->update(['name' => 'customer']);
        }

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
            PromotionSeeder::class,
        ]);

    }
    
}
