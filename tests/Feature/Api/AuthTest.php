<?php

use App\Models\User;

test('it logs in with valid credentials and returns a token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'device_name' => 'phpunit',
    ]);

    $response->assertOk()->assertJsonStructure(['token']);
    expect($user->tokens()->count())->toBe(1);
});

test('it rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'phpunit',
    ]);

    $response->assertStatus(422);
    expect($user->tokens()->count())->toBe(0);
});

test('it logs out and revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('phpunit');

    $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->postJson('/api/logout');

    $response->assertNoContent();
    expect($user->tokens()->count())->toBe(0);
});

test('protected routes require authentication', function () {
    $this->getJson('/api/sources')->assertUnauthorized();
});
