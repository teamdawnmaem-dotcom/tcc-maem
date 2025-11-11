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
    }
}
