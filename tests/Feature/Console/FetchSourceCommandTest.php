<?php

use App\Models\Item;
use App\Models\Source;
use Illuminate\Support\Facades\Http;

test('it collects items for a known rss source', function () {
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

    $this->artisan('vigie:fetch-source', ['source_id' => $source->id])
        ->assertExitCode(0);

    expect(Item::count())->toBe(1);
});

test('it fails for an unknown source', function () {
    $this->artisan('vigie:fetch-source', ['source_id' => 999])
        ->assertExitCode(1);
});

test('it fails for a source type without a collector', function () {
    $source = Source::create([
        'name' => 'Not implemented yet',
        'type' => 'twitter',
        'url_or_query' => 'irrelevant',
        'is_active' => true,
    ]);

    $this->artisan('vigie:fetch-source', ['source_id' => $source->id])
        ->assertExitCode(1);
});
