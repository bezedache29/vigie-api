<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Collecte périodique de toutes les sources actives (rss/youtube/reddit).
// Cadence volontairement modérée pour respecter les rate limits des API externes.
Schedule::command('vigie:dispatch-fetch-jobs')
    ->everyThirtyMinutes()
    ->withoutOverlapping();
