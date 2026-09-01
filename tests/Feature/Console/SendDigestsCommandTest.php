<?php

use App\Mail\DigestMail;
use App\Models\Digest;
use App\Models\Item;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('it sends a digest with eligible items and records it', function () {
    Mail::fake();

    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'summarized']);
    Summary::factory()->create(['item_id' => $item->id, 'relevance_score' => 90]);

    $this->artisan('vigie:send-digests')->assertExitCode(0);

    Mail::assertSent(DigestMail::class, fn (DigestMail $mail) => $mail->items->pluck('id')->contains($item->id));
    expect(Digest::where('user_id', $user->id)->where('channel', 'email')->exists())->toBeTrue();
});

test('it does not send anything when there are no eligible items', function () {
    Mail::fake();

    User::factory()->create();

    $this->artisan('vigie:send-digests')->assertExitCode(0);

    Mail::assertNothingSent();
    expect(Digest::count())->toBe(0);
});

test('it does not send a second daily digest the same day', function () {
    Mail::fake();

    $user = User::factory()->create();
    Digest::factory()->create(['user_id' => $user->id, 'channel' => 'email', 'sent_at' => now()]);

    $item = Item::factory()->create(['status' => 'summarized']);
    Summary::factory()->create(['item_id' => $item->id, 'relevance_score' => 90]);

    $this->artisan('vigie:send-digests')->assertExitCode(0);

    Mail::assertNothingSent();
});

test('it does not record the digest when sending fails, so it can retry later', function () {
    Mail::shouldReceive('to')->andReturnSelf();
    Mail::shouldReceive('send')->andThrow(new Exception('SMTP down'));

    $user = User::factory()->create();
    $item = Item::factory()->create(['status' => 'summarized']);
    Summary::factory()->create(['item_id' => $item->id, 'relevance_score' => 90]);

    $this->artisan('vigie:send-digests')->assertExitCode(1);

    expect(Digest::where('user_id', $user->id)->exists())->toBeFalse();
});
