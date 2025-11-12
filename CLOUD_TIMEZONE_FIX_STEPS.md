# Cloud Server Timezone Fix - Step by Step

## Current Issue
Cloud server is still creating timestamps in UTC (e.g., `2025-11-12 05:15:57`) instead of local timezone.

## Solution Steps (Do these on your Hostinger cloud server)

### Step 1: Edit .env File
1. Log into your Hostinger account
2. Go to File Manager or use SSH
3. Navigate to your Laravel project root
4. Open the `.env` file
5. Add or update this line:
   ```env
   APP_TIMEZONE=Asia/Manila
   ```
   (Replace `Asia/Manila` with your actual timezone)

### Step 2: Clear All Caches
Run these commands on your cloud server (via SSH or Hostinger terminal):

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**IMPORTANT:** Do NOT run `php artisan config:cache` after this - we want to read from .env directly.

### Step 3: Verify .env File
Make sure the `.env` file has the timezone set. You can verify by running:

```bash
grep APP_TIMEZONE .env
```

It should show:
```
APP_TIMEZONE=Asia/Manila
```

### Step 4: Test the Timezone
Create a test route temporarily to verify:

```php
// In routes/web.php (add temporarily)
Route::get('/test-tz', function() {
    return [
        'env_timezone' => env('APP_TIMEZONE'),
        'config_timezone' => config('app.timezone'),
        'mysql_timezone' => DB::selectOne("SELECT @@session.time_zone as tz")->tz ?? 'unknown',
        'php_now' => now()->format('Y-m-d H:i:s'),
        'mysql_now' => DB::selectOne("SELECT NOW() as now")->now ?? 'unknown',
    ];
});
```

Visit `https://yourdomain.com/test-tz` and check:
- `env_timezone` should show `Asia/Manila` (or your timezone)
- `mysql_timezone` should show `+08:00` (or your offset)
- `php_now` and `mysql_now` should match your local time

### Step 5: Create a New Record
After verifying, create a new record and check the `created_at` timestamp. It should now show the correct local time.

### Step 6: Remove Test Route
After testing, remove the test route from `routes/web.php`.

## If It Still Doesn't Work

### Option A: Manual MySQL Timezone Setting
1. Go to Hostinger cPanel → phpMyAdmin
2. Select your database
3. Run this SQL query:
   ```sql
   SET GLOBAL time_zone = '+08:00';
   ```
   (Replace `+08:00` with your timezone offset)

### Option B: Check Application Logs
Check `storage/logs/laravel.log` for timezone-related messages:
```bash
tail -f storage/logs/laravel.log | grep timezone
```

You should see:
```
MySQL timezone set to: +08:00 (app timezone: Asia/Manila)
```

## Common Timezone Offsets

- `Asia/Manila` → `+08:00`
- `Asia/Singapore` → `+08:00`
- `Asia/Hong_Kong` → `+08:00`
- `Asia/Tokyo` → `+09:00`
- `UTC` → `+00:00`

## Important Notes

- **Both servers must use the same timezone** for consistent sync
- **Existing records** will NOT be automatically converted
- **After changes**, you may need to restart your web server or PHP-FPM

