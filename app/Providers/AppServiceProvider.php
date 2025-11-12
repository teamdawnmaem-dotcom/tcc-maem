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
        //
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
        try {
            $appTimezone = config('app.timezone', 'UTC');
            
            // Convert timezone name to MySQL offset format if needed
            // MySQL accepts timezone names (e.g., 'Asia/Manila') or offsets (e.g., '+08:00')
            $mysqlTimezone = $appTimezone;
            
            // If it's a named timezone, try to get the offset
            try {
                $dt = new \DateTime('now', new \DateTimeZone($appTimezone));
                $offset = $dt->getOffset();
                $hours = intval($offset / 3600);
                $minutes = abs(intval(($offset % 3600) / 60));
                $mysqlTimezone = sprintf('%+03d:%02d', $hours, $minutes);
            } catch (\Exception $e) {
                // If conversion fails, use the timezone name directly (MySQL supports named timezones)
                $mysqlTimezone = $appTimezone;
            }
            
            \DB::statement("SET time_zone = '{$mysqlTimezone}'");
            \Log::info("MySQL timezone set to: {$mysqlTimezone} (app timezone: {$appTimezone})");
        } catch (\Exception $e) {
            // If timezone setting fails, log but don't break the application
            \Log::warning("Failed to set MySQL timezone: " . $e->getMessage());
        }
    }
}
