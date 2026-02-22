<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('attendance:auto-resolve')->everyThirtyMinutes();
\Illuminate\Support\Facades\Schedule::command('attendance:mark-absent')->hourly();
// Process queue jobs every minute (for shared hosting compatibility)
\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty')->everyMinute();
