<?php

use App\Models\Digest;
use App\Models\Item;
use App\Models\Source;
use App\Models\Summary;
use App\Models\User;
use App\Services\DigestBuilder;

function summarizedItem(array $itemAttributes = [], int $relevanceScore = 80): Item
{
    $item = Item::factory()->create(['status' => 'summarized', ...$itemAttributes]);
    Summary::factory()->create(['item_id' => $item->id, 'relevance_score' => $relevanceScore]);

    return $item->fresh();
}

test('it is due when the user never received a digest', function () {
    $user = User::factory()->create();

    expect((new DigestBuilder)->isDue($user))->toBeTrue();
});

test('daily digest is not due twice the same day', function () {
    $user = User::factory()->create();
    $user->preference()->create(['digest_frequency' => 'daily']);
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()]);

    expect((new DigestBuilder)->isDue($user))->toBeFalse();
});

test('daily digest is due again the next day', function () {
    $user = User::factory()->create();
    $user->preference()->create(['digest_frequency' => 'daily']);
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()->subDay()]);

    expect((new DigestBuilder)->isDue($user))->toBeTrue();
});

test('weekly digest is not due before a week has passed', function () {
    $user = User::factory()->create();
    $user->preference()->create(['digest_frequency' => 'weekly']);
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()->subDays(3)]);

    expect((new DigestBuilder)->isDue($user))->toBeFalse();
});

test('weekly digest is due after a week', function () {
    $user = User::factory()->create();
    $user->preference()->create(['digest_frequency' => 'weekly']);
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()->subDays(8)]);

    expect((new DigestBuilder)->isDue($user))->toBeTrue();
});

test('it excludes items below the relevance threshold', function () {
    $user = User::factory()->create();
    summarizedItem(relevanceScore: 30);
    $good = summarizedItem(relevanceScore: 90);

    $items = (new DigestBuilder)->eligibleItems($user);

    expect($items->pluck('id')->all())->toBe([$good->id]);
});

test('it excludes non-summarized items', function () {
    $user = User::factory()->create();
    Item::factory()->create(['status' => 'pending']);

    expect((new DigestBuilder)->eligibleItems($user))->toBeEmpty();
});

test('it only includes items from sources the user activated, when any is set', function () {
    $user = User::factory()->create();
    $followedSource = Source::factory()->create();
    $user->sources()->attach($followedSource);

    $followed = summarizedItem(['source_id' => $followedSource->id]);
    summarizedItem(['source_id' => Source::factory()->create()->id]);

    $items = (new DigestBuilder)->eligibleItems($user);

    expect($items->pluck('id')->all())->toBe([$followed->id]);
});

test('it includes items from all sources when the user activated none', function () {
    $user = User::factory()->create();
    summarizedItem();
    summarizedItem();

    expect((new DigestBuilder)->eligibleItems($user))->toHaveCount(2);
});

test('it filters by keyword when the user has some', function () {
    $user = User::factory()->create();
    $user->preference()->create(['keywords' => ['laravel']]);

    $matching = summarizedItem(['title' => 'Laravel 13 est sorti']);
    summarizedItem(['title' => 'React 19 news']);

    $items = (new DigestBuilder)->eligibleItems($user);

    expect($items->pluck('id')->all())->toBe([$matching->id]);
});

test('it only includes items created since the last digest', function () {
    $user = User::factory()->create();
    $user->preference()->create(['digest_frequency' => 'daily']);
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()->subHours(2)]);

    $old = summarizedItem();
    $old->forceFill(['created_at' => now()->subDays(3)])->save();

    $recent = summarizedItem();

    $items = (new DigestBuilder)->eligibleItems($user);

    expect($items->pluck('id')->all())->toBe([$recent->id]);
});
