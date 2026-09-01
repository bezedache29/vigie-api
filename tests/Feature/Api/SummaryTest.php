<?php

use App\Models\Summary;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
});

test('it lists summaries', function () {
    Summary::factory()->count(2)->create();

    $this->getJson('/api/summaries')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it shows a single summary', function () {
    $summary = Summary::factory()->create();

    $this->getJson("/api/summaries/{$summary->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $summary->id);
});
