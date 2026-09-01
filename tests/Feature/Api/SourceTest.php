<?php

use App\Models\Source;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
});

test('it lists sources', function () {
    Source::factory()->count(2)->create();

    $this->getJson('/api/sources')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it creates a source', function () {
    $payload = [
        'name' => 'Hacker News',
        'type' => 'rss',
        'url_or_query' => 'https://news.ycombinator.com/rss',
        'is_active' => true,
    ];

    $this->postJson('/api/sources', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Hacker News');

    expect(Source::count())->toBe(1);
});

test('it rejects an invalid source type', function () {
    $this->postJson('/api/sources', [
        'name' => 'Bad',
        'type' => 'not-a-real-type',
        'url_or_query' => 'x',
    ])->assertStatus(422);
});

test('it updates a source', function () {
    $source = Source::factory()->create(['is_active' => true]);

    $this->putJson("/api/sources/{$source->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

test('it deletes a source', function () {
    $source = Source::factory()->create();

    $this->deleteJson("/api/sources/{$source->id}")->assertNoContent();

    expect(Source::find($source->id))->toBeNull();
});
