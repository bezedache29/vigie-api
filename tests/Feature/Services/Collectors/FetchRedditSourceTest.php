<?php

use App\Models\Item;
use App\Models\Source;
use App\Services\Collectors\FetchRedditSource;
use Illuminate\Support\Facades\Http;

function makeRedditSource(): Source
{
    return Source::create([
        'name' => 'r/programming',
        'type' => 'reddit',
        'url_or_query' => 'programming',
        'is_active' => true,
    ]);
}

test('it creates items from a subreddit listing', function () {
    $source = makeRedditSource();

    Http::fake([
        'https://www.reddit.com/api/v1/access_token' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], 200),
        'https://oauth.reddit.com/*' => Http::response([
            'data' => [
                'children' => [
                    ['data' => [
                        'name' => 't3_a',
                        'title' => 'Post A',
                        'permalink' => '/r/programming/comments/a/post_a/',
                        'selftext' => 'Content A',
                        'created_utc' => 1756713600,
                    ]],
                    ['data' => [
                        'name' => 't3_b',
                        'title' => 'Post B',
                        'permalink' => '/r/programming/comments/b/post_b/',
                        'selftext' => '',
                        'created_utc' => 1756717200,
                    ]],
                ],
            ],
        ], 200),
    ]);

    $items = (new FetchRedditSource)->fetch($source);

    expect($items)->toHaveCount(2)
        ->and(Item::count())->toBe(2)
        ->and(Item::where('external_id', 't3_a')->first()->url)
        ->toBe('https://reddit.com/r/programming/comments/a/post_a/');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer fake-token'));
});

test('it does not mangle a subreddit name starting with r when given the r/ prefix', function () {
    $source = Source::create([
        'name' => 'r/reactjs',
        'type' => 'reddit',
        'url_or_query' => 'r/reactjs',
        'is_active' => true,
    ]);

    Http::fake([
        'https://www.reddit.com/api/v1/access_token' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], 200),
        'https://oauth.reddit.com/*' => Http::response([
            'data' => ['children' => []],
        ], 200),
    ]);

    (new FetchRedditSource)->fetch($source);

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/r/reactjs/new'));
});

test('it skips items already known via external_id', function () {
    $source = makeRedditSource();

    Item::create([
        'source_id' => $source->id,
        'external_id' => 't3_a',
        'title' => 'Already known',
        'status' => 'pending',
    ]);

    Http::fake([
        'https://www.reddit.com/api/v1/access_token' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ], 200),
        'https://oauth.reddit.com/*' => Http::response([
            'data' => [
                'children' => [
                    ['data' => [
                        'name' => 't3_a',
                        'title' => 'Post A',
                        'permalink' => '/r/programming/comments/a/post_a/',
                        'selftext' => 'Content A',
                        'created_utc' => 1756713600,
                    ]],
                ],
            ],
        ], 200),
    ]);

    $items = (new FetchRedditSource)->fetch($source);

    expect($items)->toHaveCount(0)
        ->and(Item::count())->toBe(1);
});
