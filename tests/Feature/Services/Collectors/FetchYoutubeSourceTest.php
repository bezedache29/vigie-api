<?php

use App\Models\Item;
use App\Models\Source;
use App\Services\Collectors\FetchYoutubeSource;
use Illuminate\Support\Facades\Http;

function makeYoutubeSource(): Source
{
    return Source::create([
        'name' => 'Example Channel',
        'type' => 'youtube',
        'url_or_query' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
        'is_active' => true,
    ]);
}

function fakePlaylistItemsResponse(array $items): array
{
    return ['items' => $items];
}

test('it rejects a source not configured with a UC... channel id', function () {
    $source = Source::create([
        'name' => 'Bad config',
        'type' => 'youtube',
        'url_or_query' => '@somechannel',
        'is_active' => true,
    ]);

    (new FetchYoutubeSource)->fetch($source);
})->throws(RuntimeException::class);

test('it creates items from the channel uploads playlist', function () {
    $source = makeYoutubeSource();

    Http::fake([
        'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response(fakePlaylistItemsResponse([
            [
                'snippet' => [
                    'title' => 'Video A',
                    'description' => 'Description A',
                    'publishedAt' => '2026-09-01T10:00:00Z',
                    'resourceId' => ['videoId' => 'video-a'],
                ],
            ],
            [
                'snippet' => [
                    'title' => 'Video B',
                    'description' => 'Description B',
                    'publishedAt' => '2026-09-01T11:00:00Z',
                    'resourceId' => ['videoId' => 'video-b'],
                ],
            ],
        ]), 200),
    ]);

    $items = (new FetchYoutubeSource)->fetch($source);

    expect($items)->toHaveCount(2)
        ->and(Item::count())->toBe(2)
        ->and(Item::where('external_id', 'video-a')->first()->url)
        ->toBe('https://www.youtube.com/watch?v=video-a');

    Http::assertSent(fn ($request) => $request['playlistId'] === 'UU_x5XG1OV2P6uZZ5FSM9Ttw');
});

test('it skips playlist entries without a videoId instead of crashing', function () {
    $source = makeYoutubeSource();

    Http::fake([
        'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response(fakePlaylistItemsResponse([
            [
                'snippet' => [
                    'title' => 'Private video',
                    'description' => '',
                    'publishedAt' => '2026-09-01T10:00:00Z',
                    'resourceId' => [],
                ],
            ],
            [
                'snippet' => [
                    'title' => 'Video B',
                    'description' => 'Description B',
                    'publishedAt' => '2026-09-01T11:00:00Z',
                    'resourceId' => ['videoId' => 'video-b'],
                ],
            ],
        ]), 200),
    ]);

    $items = (new FetchYoutubeSource)->fetch($source);

    expect($items)->toHaveCount(1)
        ->and(Item::count())->toBe(1)
        ->and(Item::first()->external_id)->toBe('video-b');
});

test('it skips items already known via external_id', function () {
    $source = makeYoutubeSource();

    Item::create([
        'source_id' => $source->id,
        'external_id' => 'video-a',
        'title' => 'Already known',
        'status' => 'pending',
    ]);

    Http::fake([
        'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response(fakePlaylistItemsResponse([
            [
                'snippet' => [
                    'title' => 'Video A',
                    'description' => '',
                    'publishedAt' => '2026-09-01T10:00:00Z',
                    'resourceId' => ['videoId' => 'video-a'],
                ],
            ],
        ]), 200),
    ]);

    $items = (new FetchYoutubeSource)->fetch($source);

    expect($items)->toHaveCount(0)
        ->and(Item::count())->toBe(1);
});
