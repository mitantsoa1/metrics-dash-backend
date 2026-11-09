<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:send-scheduled')->everyMinute();

// PU-52 : agrégats par fenêtre de 10 minutes ; consolidation journalière chaque heure
Schedule::command('metrics:aggregate-windows')->everyTenMinutes()->withoutOverlapping();
Schedule::command('metrics:aggregate')->hourly()->withoutOverlapping();
