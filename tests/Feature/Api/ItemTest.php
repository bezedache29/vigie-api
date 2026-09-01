<?php

use App\Models\Item;
use App\Models\Summary;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
});

test('it lists items with their summary', function () {
    $item = Item::factory()->create(['status' => 'summarized']);
    Summary::factory()->create(['item_id' => $item->id]);

    $this->getJson('/api/items')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.summary.item_id', $item->id);
});

test('it shows a single item', function () {
    $item = Item::factory()->create();

    $this->getJson("/api/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $item->id);
});

test('it allows marking an item as ignored', function () {
    $item = Item::factory()->create(['status' => 'pending']);

    $this->putJson("/api/items/{$item->id}", ['status' => 'ignored'])
        ->assertOk()
        ->assertJsonPath('data.status', 'ignored');
});

test('it rejects setting a system-managed status manually', function () {
    $item = Item::factory()->create(['status' => 'pending']);

    $this->putJson("/api/items/{$item->id}", ['status' => 'summarized'])
        ->assertStatus(422);
});
