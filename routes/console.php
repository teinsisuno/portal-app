<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lifecycle subscription: trial/aktif yang lewat jatuh tempo → past_due.
// Di production pastikan cron tiap menit menjalankan `php artisan schedule:run`.
Schedule::command('subscriptions:expire')->hourly();
