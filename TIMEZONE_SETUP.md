# Timezone Configuration Guide

## Issue
The database was storing timestamps in UTC, but the actual timezone should be different (e.g., `Asia/Manila` which is UTC+8).

Example: Database shows `2025-11-12 04:49:19` but actual time is `12:49pm` (8 hours difference = UTC+8).

## Solution

### 1. Set Application Timezone

Edit `.env` file:
```env
APP_TIMEZONE=Asia/Manila
```

Or edit `config/app.php`:
```php
'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
```

### 2. Set MySQL Timezone

The system now automatically sets MySQL timezone on application boot (in `AppServiceProvider`).

**For Cloud Server:**
- Make sure the `.env` file has the correct timezone
- The timezone will be set automatically when the application starts

**For Local Server:**
- Make sure the `.env` file has the correct timezone
- The timezone will be set automatically when the application starts

### 3. Verify Timezone

Check the logs after application starts:
```
MySQL timezone set to: +08:00 (app timezone: Asia/Manila)
```

### 4. Common Timezones

- `Asia/Manila` - Philippines (UTC+8)
- `Asia/Singapore` - Singapore (UTC+8)
- `Asia/Hong_Kong` - Hong Kong (UTC+8)
- `Asia/Tokyo` - Japan (UTC+9)
- `UTC` - Coordinated Universal Time

### 5. Important Notes

- **Existing Records**: Timestamps already stored in the database will NOT be automatically converted. Only new records will use the correct timezone.
- **Sync**: When syncing between local and cloud, timestamps are preserved as-is (exact copy), so both servers should use the same timezone.
- **Database Default**: MySQL's `CURRENT_TIMESTAMP` and `ON UPDATE CURRENT_TIMESTAMP` will now use the configured timezone.

## Testing

1. Create a new record
2. Check the `created_at` and `updated_at` timestamps
3. Verify they match your local time (not UTC)

