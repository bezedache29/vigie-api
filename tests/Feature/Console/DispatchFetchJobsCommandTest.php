<?php

use App\Jobs\FetchSource;
use App\Models\Source;
use Illuminate\Support\Facades\Bus;

test('it dispatches a fetch job for each active source with a collector', function () {
    Bus::fake([FetchSource::class]);

    $rss = Source::create([
        'name' => 'RSS active',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss',
        'is_active' => true,
    ]);

    Source::create([
        'name' => 'RSS inactive',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss-2',
        'is_active' => false,
    ]);

    Source::create([
        'name' => 'Not implemented yet',
        'type' => 'twitter',
        'url_or_query' => 'irrelevant',
        'is_active' => true,
    ]);

    $this->artisan('vigie:dispatch-fetch-jobs')->assertExitCode(0);

    Bus::assertDispatchedTimes(FetchSource::class, 1);
    Bus::assertDispatched(fn (FetchSource $job) => $job->source->is($rss));
});
