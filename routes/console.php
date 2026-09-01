<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Veille 2x/jour (matin + soir), pas de polling continu.
Schedule::command('vigie:dispatch-fetch-jobs')
    ->twiceDaily(11, 18)
    ->timezone('Europe/Paris')
    ->withoutOverlapping();

// Digest email : après la collecte du soir, le temps que le résumé IA
// (asynchrone) ait traité les nouveaux items.
Schedule::command('vigie:send-digests')
    ->dailyAt('18:30')
    ->timezone('Europe/Paris')
    ->withoutOverlapping();
