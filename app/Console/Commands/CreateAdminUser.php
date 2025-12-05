<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--email=} {--name=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating admin user...');

        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'permissions' => null]
        );

        if ($adminRole->name !== 'admin') {
            $adminRole->update(['name' => 'admin']);
        }

        // Get user details
        $email = $this->option('email') ?: $this->ask('Enter admin email');
        $name = $this->option('name') ?: $this->ask('Enter admin name');
        $password = $this->option('password') ?: $this->secret('Enter admin password (min 8 characters)');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address!');
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists!");
            return 1;
        }

        // Validate password
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters!');
            return 1;
        }

        try {
            DB::transaction(function () use ($email, $name, $password, $adminRole) {
                // Create the user
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => $adminRole->id,
                    'status' => 'active',
                ]);

                // Create admin record (optional - if admins table is used)
                Admin::firstOrCreate(
                    ['user_id' => $user->id],
                    ['privilege_level' => 5] // Highest privilege level
                );

                $this->info("✓ Admin user created successfully!");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['ID', $user->id],
                        ['Name', $user->name],
                        ['Email', $user->email],
                        ['Role', $adminRole->name],
                        ['Status', $user->status],
                    ]
                );
            });
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
