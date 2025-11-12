<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Set MySQL timezone early in the connection lifecycle
        // This ensures timezone is set before any database queries
        $this->app->resolving('db', function ($db) {
            try {
                $appTimezone = config('app.timezone', 'UTC');
                
                // Always convert to offset format to avoid timezone table dependency
                $mysqlTimezone = '+00:00'; // Default to UTC offset
                
                try {
                    $dt = new \DateTime('now', new \DateTimeZone($appTimezone));
                    $offset = $dt->getOffset();
                    $hours = intval($offset / 3600);
                    $minutes = abs(intval(($offset % 3600) / 60));
                    $mysqlTimezone = sprintf('%+03d:%02d', $hours, $minutes);
                } catch (\Exception $e) {
                    $mysqlTimezone = '+00:00';
                }
                
                // Set timezone for the default connection
                \DB::statement("SET time_zone = '{$mysqlTimezone}'");
            } catch (\Exception $e) {
                // Silently fail - timezone setting is not critical
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom scheduler frequency: everyTenSeconds()
        // This makes the command run every time schedule:run is called
        // (when schedule:run is called every 10 seconds, this will execute)
        Event::macro('everyTenSeconds', function () {
            return $this->cron('* * * * *')->when(function () {
                return true; // Always run when schedule:run is called
            });
        });
        
        // Set MySQL timezone to match application timezone
        // This ensures timestamps are stored correctly in the database
        // Note: MySQL requires timezone tables to be populated for named timezones
        // We use offset format (e.g., '+08:00') which works without timezone tables
        try {
            $appTimezone = config('app.timezone', 'UTC');
            
            // Always convert to offset format to avoid timezone table dependency
            // MySQL offset format: '+08:00', '-05:00', '+00:00' for UTC
            $mysqlTimezone = '+00:00'; // Default to UTC offset
            
            try {
                $dt = new \DateTime('now', new \DateTimeZone($appTimezone));
                $offset = $dt->getOffset();
                $hours = intval($offset / 3600);
                $minutes = abs(intval(($offset % 3600) / 60));
                $mysqlTimezone = sprintf('%+03d:%02d', $hours, $minutes);
            } catch (\Exception $e) {
                // If conversion fails, default to UTC offset
                $mysqlTimezone = '+00:00';
                \Log::warning("Failed to convert timezone '{$appTimezone}' to offset, using UTC (+00:00)");
            }
            
            \DB::statement("SET time_zone = '{$mysqlTimezone}'");
            \Log::info("MySQL timezone set to: {$mysqlTimezone} (app timezone: {$appTimezone})");
        } catch (\Exception $e) {
            // If timezone setting fails, log but don't break the application
            \Log::warning("Failed to set MySQL timezone: " . $e->getMessage());
        }
    }
}
