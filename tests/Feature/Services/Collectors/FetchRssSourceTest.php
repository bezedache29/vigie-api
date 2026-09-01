<?php

use App\Models\Item;
use App\Models\Source;
use App\Services\Collectors\FetchRssSource;
use Illuminate\Support\Facades\Http;

function fakeRssFeed(array $items): string
{
    $entries = collect($items)->map(fn (array $item) => sprintf(
        '<item><title>%s</title><link>%s</link><guid>%s</guid><pubDate>%s</pubDate><description>%s</description></item>',
        $item['title'],
        $item['link'],
        $item['guid'],
        $item['pubDate'],
        $item['description'],
    ))->implode('');

    return "<rss version=\"2.0\"><channel><title>Feed</title><link>https://example.test</link>{$entries}</channel></rss>";
}

test('it creates items from a rss feed', function () {
    $source = Source::create([
        'name' => 'Example Feed',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.test/rss' => Http::response(fakeRssFeed([
            [
                'title' => 'Article A',
                'link' => 'https://example.test/a',
                'guid' => 'guid-a',
                'pubDate' => 'Tue, 01 Sep 2026 10:00:00 +0000',
                'description' => 'Content A',
            ],
            [
                'title' => 'Article B',
                'link' => 'https://example.test/b',
                'guid' => 'guid-b',
                'pubDate' => 'Tue, 01 Sep 2026 11:00:00 +0000',
                'description' => 'Content B',
            ],
        ]), 200),
    ]);

    $items = (new FetchRssSource)->fetch($source);

    expect($items)->toHaveCount(2)
        ->and(Item::count())->toBe(2)
        ->and(Item::where('external_id', 'guid-a')->exists())->toBeTrue();
});

test('it skips items already known via external_id', function () {
    $source = Source::create([
        'name' => 'Example Feed',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss',
        'is_active' => true,
    ]);

    Item::create([
        'source_id' => $source->id,
        'external_id' => 'guid-a',
        'title' => 'Already known',
        'status' => 'pending',
    ]);

    Http::fake([
        'https://example.test/rss' => Http::response(fakeRssFeed([
            [
                'title' => 'Article A',
                'link' => 'https://example.test/a',
                'guid' => 'guid-a',
                'pubDate' => 'Tue, 01 Sep 2026 10:00:00 +0000',
                'description' => 'Content A',
            ],
        ]), 200),
    ]);

    $items = (new FetchRssSource)->fetch($source);

    expect($items)->toHaveCount(0)
        ->and(Item::count())->toBe(1);
});
