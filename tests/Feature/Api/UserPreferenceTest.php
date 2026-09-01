<?php

use App\Models\Source;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('it creates a default preference on first access', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/preferences')
        ->assertOk()
        ->assertJsonPath('data.digest_frequency', 'daily')
        ->assertJsonPath('data.active_source_ids', []);

    expect($user->preference()->exists())->toBeTrue();
});

test('it updates keywords, digest frequency and active sources', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $sources = Source::factory()->count(2)->create();

    $this->putJson('/api/preferences', [
        'keywords' => ['laravel', 'php'],
        'digest_frequency' => 'weekly',
        'source_ids' => $sources->pluck('id')->all(),
    ])
        ->assertOk()
        ->assertJsonPath('data.keywords', ['laravel', 'php'])
        ->assertJsonPath('data.digest_frequency', 'weekly')
        ->assertJsonCount(2, 'data.active_source_ids');

    expect($user->sources()->count())->toBe(2);
});

test('it rejects an unknown source id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->putJson('/api/preferences', ['source_ids' => [999]])
        ->assertStatus(422);
});
