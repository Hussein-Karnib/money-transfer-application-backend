<?php

/**
 * Insert/Update Admin User in Database
 * This script will create or update the admin user
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Inserting Admin User ===\n\n";

try {
    DB::transaction(function () {
        // 1. Ensure admin role exists
        $adminRole = Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'permissions' => null]
        );

        if ($adminRole->name !== 'admin') {
            $adminRole->update(['name' => 'admin']);
        }

        echo "✓ Admin role ensured (ID: {$adminRole->id})\n";

        // 2. Create or update admin user
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Main Admin',
                'password' => Hash::make('Admin123!'),
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]
        );

        echo "✓ Admin user " . ($user->wasRecentlyCreated ? "created" : "updated") . " (ID: {$user->id})\n";
        echo "  - Email: {$user->email}\n";
        echo "  - Name: {$user->name}\n";
        echo "  - Status: {$user->status}\n";
        echo "  - Password: Admin123!\n";

        // 3. Create admin record
        $admin = Admin::firstOrCreate(
            ['user_id' => $user->id],
            ['privilege_level' => 5]
        );

        echo "✓ Admin record " . ($admin->wasRecentlyCreated ? "created" : "exists") . " (Privilege Level: {$admin->privilege_level})\n";

        echo "\n✅ Admin user successfully inserted/updated in database!\n";
        echo "\nLogin credentials:\n";
        echo "  Email: admin@example.com\n";
        echo "  Password: Admin123!\n";
    });
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

