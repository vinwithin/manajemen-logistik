<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule database backup daily at 2:00 AM
Schedule::command('db:backup')->dailyAt('02:00')->name('database-backup')->withoutOverlapping();
