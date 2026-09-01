<?php

use App\Models\Digest;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
});

test('it lists digests', function () {
    Digest::factory()->count(2)->create();

    $this->getJson('/api/digests')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it shows a single digest', function () {
    $digest = Digest::factory()->create();

    $this->getJson("/api/digests/{$digest->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $digest->id);
});
