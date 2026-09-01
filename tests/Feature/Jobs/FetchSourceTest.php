<?php

use App\Jobs\FetchSource;
use App\Jobs\SummarizeItem;
use App\Models\Item;
use App\Models\Source;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

test('it fetches new items and dispatches a summarize job for each', function () {
    Bus::fake([SummarizeItem::class]);

    $source = Source::create([
        'name' => 'Example Feed',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.test/rss' => Http::response(
            '<rss version="2.0"><channel><title>Feed</title><item>'
            .'<title>Article A</title><link>https://example.test/a</link>'
            .'<guid>guid-a</guid><pubDate>Tue, 01 Sep 2026 10:00:00 +0000</pubDate>'
            .'<description>Content A</description></item></channel></rss>',
            200
        ),
    ]);

    (new FetchSource($source))->handle(app(App\Services\Collectors\CollectorResolver::class));

    expect(Item::count())->toBe(1);
    Bus::assertDispatched(SummarizeItem::class);
});

test('it does nothing for a source type without a collector', function () {
    $source = Source::create([
        'name' => 'Not implemented yet',
        'type' => 'twitter',
        'url_or_query' => 'irrelevant',
        'is_active' => true,
    ]);

    (new FetchSource($source))->handle(app(App\Services\Collectors\CollectorResolver::class));

    expect(Item::count())->toBe(0);
});
