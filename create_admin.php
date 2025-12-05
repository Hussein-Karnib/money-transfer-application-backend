<?php

/**
 * Simple script to create an admin user
 * Run: php create_admin.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Create Admin User ===\n\n";

// Ensure admin role exists
$adminRole = Role::firstOrCreate(
    ['id' => 1],
    ['name' => 'admin', 'permissions' => null]
);

if ($adminRole->name !== 'admin') {
    $adminRole->update(['name' => 'admin']);
}

// Get user details
echo "Enter admin email: ";
$email = trim(fgets(STDIN));

echo "Enter admin name: ";
$name = trim(fgets(STDIN));

echo "Enter admin password (min 8 characters): ";
$password = trim(fgets(STDIN));

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Invalid email address!\n";
    exit(1);
}

if (User::where('email', $email)->exists()) {
    echo "ERROR: User with email '{$email}' already exists!\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "ERROR: Password must be at least 8 characters!\n";
    exit(1);
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

        // Create admin record
        Admin::firstOrCreate(
            ['user_id' => $user->id],
            ['privilege_level' => 5]
        );

        echo "\n✓ Admin user created successfully!\n";
        echo "ID: {$user->id}\n";
        echo "Name: {$user->name}\n";
        echo "Email: {$user->email}\n";
        echo "Role: {$adminRole->name}\n";
        echo "Status: {$user->status}\n";
    });
} catch (\Exception $e) {
    echo "ERROR: Failed to create admin user: " . $e->getMessage() . "\n";
    exit(1);
}

