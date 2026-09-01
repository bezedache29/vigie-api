<?php

use App\Jobs\SummarizeItem;
use App\Models\Item;
use App\Models\Source;
use App\Models\Summary;
use Illuminate\Support\Facades\Http;

function makeItemForSummarize(array $attributes = []): Item
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

test('it creates a summary and marks the item as summarized', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'summary' => 'Laravel 13 apporte de nouvelles fonctionnalités.',
                    'tags' => ['laravel'],
                    'relevance_score' => 90,
                ])]],
            ],
        ], 200),
    ]);

    $item = makeItemForSummarize();

    (new SummarizeItem($item))->handle(app(App\Services\OpenAiSummarizer::class));

    $item->refresh();

    expect($item->status)->toBe('summarized')
        ->and(Summary::where('item_id', $item->id)->exists())->toBeTrue();

    $summary = Summary::where('item_id', $item->id)->first();
    expect($summary->summary_text)->toBe('Laravel 13 apporte de nouvelles fonctionnalités.')
        ->and($summary->tags)->toBe(['laravel'])
        ->and($summary->relevance_score)->toBe(90)
        ->and($summary->model_used)->toBe(config('llm.openai.model'));
});

test('it marks the item as error when openai fails', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response('server error', 500),
    ]);

    $item = makeItemForSummarize();

    expect(fn () => (new SummarizeItem($item))->handle(app(App\Services\OpenAiSummarizer::class)))
        ->toThrow(Exception::class);

    expect($item->refresh()->status)->toBe('error')
        ->and(Summary::where('item_id', $item->id)->exists())->toBeFalse();
});
