<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:expire --limit=100')->everyMinute()->withoutOverlapping(5);
Schedule::command('payments:reconcile --limit=20')->everyFiveMinutes()->withoutOverlapping(5);
Schedule::command('auth:clear-resets')->hourly()->withoutOverlapping(5);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
