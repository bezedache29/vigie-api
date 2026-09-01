<?php

namespace App\Console\Commands;

use App\Jobs\FetchSource;
use App\Models\Source;
use App\Services\Collectors\CollectorResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vigie:dispatch-fetch-jobs')]
#[Description('Planifie la collecte de toutes les sources actives (dispatch un job par source)')]
class DispatchFetchJobsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CollectorResolver $resolver): int
    {
        $dispatched = 0;

        Source::query()
            ->where('is_active', true)
            ->each(function (Source $source) use ($resolver, &$dispatched) {
                if (! $resolver->resolve($source)) {
                    return;
                }

                FetchSource::dispatch($source);
                $dispatched++;
            });

        $this->info("{$dispatched} job(s) de collecte dispatché(s).");

        return self::SUCCESS;
    }
}
