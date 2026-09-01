<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Veille 2x/jour (matin + soir), pas de polling continu.
Schedule::command('vigie:dispatch-fetch-jobs')
    ->twiceDaily(8, 18)
    ->timezone('Europe/Paris')
    ->withoutOverlapping();
