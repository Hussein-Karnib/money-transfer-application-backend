# Setup Guide - Fixing 500 Error

## Issues Found:
1. ❌ **Missing `vendor` folder** - Dependencies not installed
2. ❌ **Missing `APP_KEY`** - Laravel requires this for encryption
3. ❌ **Database not migrated** - Tables don't exist yet
4. ❌ **PHP not in PATH** - Can't run Laravel commands

## Step-by-Step Fix:

### Step 1: Install PHP (if not installed)

**Option A: Install XAMPP (Recommended for Windows)**
1. Download from: https://www.apachefriends.org/
2. Install XAMPP (includes PHP, MySQL, Apache)
3. PHP will be at: `C:\xampp\php\php.exe`

**Option B: Install PHP directly**
1. Download from: https://windows.php.net/download/
2. Extract to `C:\php`
3. Add `C:\php` to your system PATH

### Step 2: Add PHP to PATH (if using XAMPP)

1. Open System Properties → Environment Variables
2. Edit "Path" in System variables
3. Add: `C:\xampp\php`
4. Restart your terminal/PowerShell

### Step 3: Verify PHP Installation

```powershell
php --version
```

### Step 4: Install Dependencies

```powershell
composer install
```

### Step 5: Generate APP_KEY

```powershell
php artisan key:generate
```

### Step 6: Run Migrations

```powershell
php artisan migrate
```

### Step 7: Test API Endpoints

After completing the above steps, your API should work. Test with Postman:

**Public Endpoints (No Auth Required):**
- `GET /api/currencies` - List all currencies
- `GET /api/currencies/{code}` - Get specific currency
- `GET /api/exchange-rates` - List exchange rates
- `GET /api/exchange-rates/convert?amount=100&from=USD&to=EUR` - Convert currency

**Authenticated Endpoints (Require Login):**
- All other endpoints require authentication

## Quick Fix Script (If PHP is at C:\xampp\php\php.exe):

```powershell
# Set PHP path
$phpPath = "C:\xampp\php\php.exe"

# Install dependencies
& $phpPath "$(Get-Command composer).Source" install

# Generate key
& $phpPath artisan key:generate

# Run migrations
& $phpPath artisan migrate
```

## Alternative: Use Laravel Sail (Docker)

If you have Docker installed:

```powershell
# Install dependencies via Docker
docker run --rm -v ${PWD}:/app composer install

# Then use Sail for everything else
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

## Common 500 Error Causes:

1. **Missing vendor folder** → Run `composer install`
2. **Missing APP_KEY** → Run `php artisan key:generate`
3. **Database tables don't exist** → Run `php artisan migrate`
4. **Database connection error** → Check `.env` file DB settings
5. **Missing PHP extensions** → Install required PHP extensions

## Check Laravel Logs:

After trying an API request, check the error:
```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

This will show you the exact error causing the 500 response.

