<?php

/**
 * Create Database Script
 * This script will create the 'finalproject' database if it doesn't exist
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "=== Creating Database ===\n\n";

// Get database configuration
$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$database = env('DB_DATABASE', 'finalproject');

echo "Database Configuration:\n";
echo "  Host: {$host}\n";
echo "  Port: {$port}\n";
echo "  Username: {$username}\n";
echo "  Database: {$database}\n\n";

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    $exists = $stmt->rowCount() > 0;

    if ($exists) {
        echo "✓ Database '{$database}' already exists.\n";
    } else {
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '{$database}' created successfully!\n";
    }

    echo "\n✅ Database setup complete!\n";
    echo "\nNext steps:\n";
    echo "  1. Run: php artisan migrate\n";
    echo "  2. Run: php artisan db:seed (optional)\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "  1. MySQL server is running\n";
    echo "  2. Database credentials in .env file are correct\n";
    echo "  3. User has permission to create databases\n";
    exit(1);
}

