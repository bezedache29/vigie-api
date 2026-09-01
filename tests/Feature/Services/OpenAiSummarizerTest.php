<?php

use App\Models\Item;
use App\Models\Source;
use App\Services\OpenAiSummarizer;
use Illuminate\Support\Facades\Http;

function makeItem(array $attributes = []): Item
{
    $source = Source::create([
        'name' => 'Example Feed',
        'type' => 'rss',
        'url_or_query' => 'https://example.test/rss',
        'is_active' => true,
    ]);

    return Item::create([
        'source_id' => $source->id,
        'external_id' => 'guid-a',
        'title' => 'Laravel 13 est sorti',
        'url' => 'https://example.test/a',
        'raw_content' => 'Contenu de test sur Laravel 13.',
        'status' => 'pending',
        ...$attributes,
    ]);
}

function fakeOpenAiResponse(array $payload): array
{
    return [
        'choices' => [
            ['message' => ['content' => json_encode($payload)]],
        ],
    ];
}

test('it parses a valid openai response into a summary payload', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(fakeOpenAiResponse([
            'summary' => 'Laravel 13 apporte de nouvelles fonctionnalités.',
            'tags' => ['laravel', 'php'],
            'relevance_score' => 87,
        ]), 200),
    ]);

    $result = (new OpenAiSummarizer)->summarize(makeItem());

    expect($result)->toBe([
        'summary_text' => 'Laravel 13 apporte de nouvelles fonctionnalités.',
        'tags' => ['laravel', 'php'],
        'relevance_score' => 87,
    ]);
});

test('it clamps an out-of-range relevance_score', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(fakeOpenAiResponse([
            'summary' => 'Résumé.',
            'tags' => [],
            'relevance_score' => 150,
        ]), 200),
    ]);

    $result = (new OpenAiSummarizer)->summarize(makeItem());

    expect($result['relevance_score'])->toBe(100);
});

test('it truncates raw_content before sending it to openai', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(fakeOpenAiResponse([
            'summary' => 'Résumé.',
            'tags' => [],
            'relevance_score' => 50,
        ]), 200),
    ]);

    $item = makeItem(['raw_content' => str_repeat('a', 9000)]);

    (new OpenAiSummarizer)->summarize($item);

    Http::assertSent(function ($request) {
        $userMessage = collect($request->data()['messages'])
            ->firstWhere('role', 'user')['content'];

        return mb_strlen($userMessage) < 9000;
    });
});

test('it throws when summary is not a string', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(fakeOpenAiResponse([
            'summary' => ['text' => 'not a plain string'],
            'tags' => [],
            'relevance_score' => 50,
        ]), 200),
    ]);

    (new OpenAiSummarizer)->summarize(makeItem());
})->throws(RuntimeException::class);

test('it throws on an invalid json response', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(fakeOpenAiResponse([
            'foo' => 'bar',
        ]), 200),
    ]);

    (new OpenAiSummarizer)->summarize(makeItem());
})->throws(RuntimeException::class);
