-- SQL Query to Insert Admin User
-- 
-- IMPORTANT: You need to generate a bcrypt password hash first!
-- Use this PHP command to get the hash: 
-- php -r "echo password_hash('YourPassword123', PASSWORD_BCRYPT);"
--
-- Or run: php artisan tinker
-- Then: Hash::make('YourPassword123')

-- Step 1: Ensure admin role exists (if not already created)
INSERT INTO roles (id, name, permissions, created_at, updated_at)
VALUES (1, 'admin', '[]', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'admin';

-- Step 2: Insert the admin user
-- Replace 'YOUR_EMAIL_HERE', 'Admin Name', and 'YOUR_BCRYPT_HASH_HERE' with actual values
INSERT INTO users (
    role_id,
    name,
    email,
    password,
    status,
    created_at,
    updated_at
) VALUES (
    1,                              -- role_id: 1 = admin
    'Admin Name',                   -- Change this to your admin name
    'admin@example.com',            -- Change this to your admin email
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- This is bcrypt hash for 'password' - REPLACE WITH YOUR HASH
    'active',                       -- status: active
    NOW(),
    NOW()
);

-- Step 3: Get the user ID from the insert above (or run: SELECT id FROM users WHERE email = 'admin@example.com';)
-- Then insert into admins table (replace USER_ID with the actual user id from step 2)
-- Example: If user ID is 1, replace USER_ID below with 1

-- First, let's get the user ID (run this after step 2):
-- SET @user_id = (SELECT id FROM users WHERE email = 'admin@example.com' LIMIT 1);

-- Then insert into admins table:
INSERT INTO admins (
    user_id,
    privilege_level,
    created_at,
    updated_at
) VALUES (
    @user_id,                       -- This will use the user_id from above
    5,                              -- privilege_level: 5 = highest level
    NOW(),
    NOW()
);

-- ========================================
-- COMPLETE QUERY (All in one with example values):
-- ========================================
-- 
-- 1. First generate your password hash:
--    Run: php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT) . PHP_EOL;"
--
-- 2. Then run these queries (replace the hash and values):

-- Ensure admin role
INSERT INTO roles (id, name, permissions, created_at, updated_at)
VALUES (1, 'admin', '[]', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'admin';

-- Insert user (replace email, name, and password hash)
INSERT INTO users (role_id, name, email, password, status, created_at, updated_at)
VALUES (
    1,
    'Admin User',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- REPLACE THIS HASH!
    'active',
    NOW(),
    NOW()
);

-- Insert admin record (using the user_id from the insert above)
INSERT INTO admins (user_id, privilege_level, created_at, updated_at)
SELECT id, 5, NOW(), NOW()
FROM users
WHERE email = 'admin@example.com'
AND NOT EXISTS (SELECT 1 FROM admins WHERE user_id = users.id);

