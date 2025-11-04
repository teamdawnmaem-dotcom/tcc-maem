<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cloud Sync Scheduler - Runs every 10 seconds
// Schedule::command('sync:cloud')->everyTenSeconds();

// Bidirectional Cloud Sync Scheduler - Runs every 10 seconds
// withoutOverlapping ensures only one sync runs at a time to avoid conflicts
Schedule::command('sync:bidirectional')
    ->everyTenSeconds()
    ->withoutOverlapping(120); // Maximum 120 seconds execution time
