<?php

use App\Models\User;

test('a user can sign in with valid credentials and receives a token', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('sign in is rejected with the wrong password', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

test('sign in is rejected for an unknown email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'whatever',
    ]);

    $response->assertStatus(422);
});

test('an unauthenticated request to a protected route is refused', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

test('an unauthenticated request without an Accept header still gets a 401, not a redirect', function () {
    // Regression guard: Laravel's default guest-redirect behavior tries to
    // route('login'), which doesn't exist in this API-only app, and 500s
    // instead of returning 401 for clients that don't send Accept: application/json.
    $response = $this->get('/api/user');

    $response->assertStatus(401);
});

test('a valid token grants access to a protected route', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()->assertJson(['id' => $user->id, 'email' => $user->email]);
});

test('sign out revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test');

    $this->postJson('/api/auth/logout', [], ['Authorization' => "Bearer {$token->plainTextToken}"])
        ->assertStatus(204);

    // Assert the actual DB effect rather than re-checking via a second
    // simulated HTTP call: Sanctum's guard caches the resolved user within
    // a single test process across sequential test-client calls, which
    // would make a follow-up getJson() unreliable here even though real,
    // separate HTTP requests correctly see the token as revoked.
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
});

test('the health endpoint stays public without a token', function () {
    $this->getJson('/api/health')->assertOk();
});
