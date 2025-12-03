# API Troubleshooting Guide - Fix 404 Error

## Problem: Getting 404 Not Found when calling `/api/auth/register`

## Solution Steps:

### Step 1: Start the Laravel Development Server

**Option A: If PHP is in your PATH:**
```powershell
php artisan serve
```

**Option B: If using XAMPP (full path):**
```powershell
& "C:\xampp\php\php.exe" artisan serve
```

**Option C: If XAMPP is on Desktop (check if php.exe exists):**
```powershell
& "C:\Users\Admin\Desktop\xampp\php\php.exe" artisan serve
```

**Expected Output:**
```
INFO  Server running on [http://127.0.0.1:8000]
```

**⚠️ IMPORTANT:** Keep this terminal window open! The server must be running.

---

### Step 2: Verify the Server is Running

Open your browser and go to:
- `http://localhost:8000` or `http://127.0.0.1:8000`

You should see the Laravel welcome page (not a 404).

---

### Step 3: Use the Correct URL in Postman

**Correct URL:**
```
POST http://localhost:8000/api/auth/register
```

**OR:**
```
POST http://127.0.0.1:8000/api/auth/register
```

**⚠️ Common Mistakes:**
- ❌ `http://localhost/api/auth/register` (missing port 8000)
- ❌ `http://localhost:8000/auth/register` (missing `/api` prefix)
- ❌ `http://localhost:8000/register` (missing `/api/auth`)

---

### Step 4: Configure Postman Request

1. **Method:** `POST`
2. **URL:** `http://localhost:8000/api/auth/register`
3. **Headers:**
   - `Content-Type: application/json`
   - `Accept: application/json`
4. **Body (raw JSON):**
   ```json
   {
       "name": "John Doe",
       "email": "john@example.com",
       "password": "password123",
       "phone": "1234567890",
       "role_id": 3
   }
   ```

**Note:** `role_id` is optional. If not provided, it defaults to `3`.

---

### Step 5: Test the Endpoint

**Expected Success Response (201 Created):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "1234567890",
        "role_id": 3,
        "status": "active",
        ...
    },
    "token": "1|xxxxxxxxxxxxx..."
}
```

**If you still get 404:**
1. Check that the server is running (Step 1)
2. Verify the URL includes `/api` prefix
3. Make sure you're using `POST` method, not `GET`
4. Try clearing route cache:
   ```powershell
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Step 6: Verify Route is Registered

Run this command to see all registered routes:
```powershell
php artisan route:list --path=api/auth
```

You should see:
```
POST   api/auth/register  ................ AuthController@register
POST   api/auth/login     ................ AuthController@login
```

---

## Quick Checklist

- [ ] Laravel server is running (`php artisan serve`)
- [ ] Server shows: `Server running on [http://127.0.0.1:8000]`
- [ ] Postman URL is: `http://localhost:8000/api/auth/register`
- [ ] Request method is `POST`
- [ ] Headers include `Content-Type: application/json`
- [ ] Body contains valid JSON with required fields

---

## Still Having Issues?

If you're still getting 404 after following all steps:

1. **Check the exact error message** - Is it a Laravel 404 or a server 404?
2. **Check the server terminal** - Are there any error messages?
3. **Try a different port:**
   ```powershell
   php artisan serve --port=8001
   ```
   Then use: `http://localhost:8001/api/auth/register`

4. **Verify PHP version:**
   ```powershell
   php -v
   ```
   Should be PHP 8.1 or higher.

