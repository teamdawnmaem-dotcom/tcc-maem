# Cloud Server Timezone Fix (Hostinger)

## Problem
Cloud server is creating timestamps in UTC (e.g., `2025-11-12 05:12:37`) instead of local timezone.

## Solution

### Step 1: Update Cloud Server .env File

On your Hostinger cloud server, edit the `.env` file and add/update:

```env
APP_TIMEZONE=Asia/Manila
```

(Replace `Asia/Manila` with your actual timezone if different)

### Step 2: Clear Config Cache on Cloud Server

SSH into your Hostinger server or use their file manager/terminal, then run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### Step 3: Verify Timezone is Set

Create a test route or check logs to verify:

```php
// In routes/web.php or routes/api.php (temporary test)
Route::get('/test-timezone', function() {
    return [
        'app_timezone' => config('app.timezone'),
        'mysql_timezone' => DB::selectOne("SELECT @@session.time_zone as timezone"),
        'current_time' => now()->format('Y-m-d H:i:s'),
        'mysql_now' => DB::selectOne("SELECT NOW() as now"),
    ];
});
```

Visit the route and check if `mysql_timezone` shows `+08:00` (or your timezone offset).

### Step 4: Test with New Record

1. Create a new record on the cloud server
2. Check the `created_at` timestamp
3. It should now show the correct local time (not UTC)

## Alternative: Manual MySQL Timezone Setting

If the automatic setting doesn't work, you can manually set it in your Hostinger MySQL:

1. Go to Hostinger cPanel → phpMyAdmin
2. Select your database
3. Run this SQL:

```sql
SET GLOBAL time_zone = '+08:00';
```

Or for the current session only:

```sql
SET time_zone = '+08:00';
```

## Important Notes

- **Both servers must use the same timezone** for consistent sync
- **Existing records** will NOT be automatically converted - only new records will use the correct timezone
- **After changing timezone**, you may need to restart your application/web server

## Common Timezones

- `Asia/Manila` → `+08:00` (Philippines)
- `Asia/Singapore` → `+08:00` (Singapore)
- `Asia/Hong_Kong` → `+08:00` (Hong Kong)
- `Asia/Tokyo` → `+09:00` (Japan)
- `UTC` → `+00:00` (UTC)

