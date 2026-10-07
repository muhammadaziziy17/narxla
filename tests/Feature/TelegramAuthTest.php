<?php

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/**
 * Test uchun imzolash kaliti va unga mos JWKS (HS256 — portativ, openssl shart emas).
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function telegramSigningKey(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $secret = 'test-telegram-signing-secret-min-32-bytes-long!';
    $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

    return $cached = [$secret, [
        'keys' => [[
            'kty' => 'oct',
            'alg' => 'HS256',
            'use' => 'sig',
            'kid' => 'test-key',
            'k' => $encode($secret),
        ]],
    ]];
}

/**
 * Telegram token va JWKS endpointlarini soxtalashtiradi.
 *
 * @param  array<string, mixed>  $claims
 * @param  array<string, mixed>|null  $jwks
 */
function fakeTelegramOauth(array $claims, ?array $jwks = null): void
{
    [$secret, $realJwks] = telegramSigningKey();

    Http::preventStrayRequests();
    Http::fake([
        '*oauth.telegram.org/token*' => Http::response([
            'access_token' => 'telegram-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'id_token' => JWT::encode($claims, $secret, 'HS256', 'test-key'),
        ]),
        '*oauth.telegram.org/.well-known/jwks.json*' => Http::response($jwks ?? $realJwks),
    ]);
}

/**
 * Telegram OIDC oqimini boshlab, sessiyadagi state'ni qaytaradi.
 */
function telegramState(): string
{
    test()->get(route('auth.telegram.redirect'))->assertRedirect();

    return (string) session('telegram_oauth_state');
}

beforeEach(function () {
    config([
        'services.telegram.client_id' => 'test-client',
        'services.telegram.client_secret' => 'test-secret',
        'services.telegram.redirect' => '/auth/telegram/callback',
    ]);
});

test('login page shows the telegram login action', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('auth.telegram.redirect'));
});

test('telegram login redirects to the authorization endpoint with pkce', function () {
    $this->get(route('auth.telegram.redirect'))
        ->assertRedirect();

    $redirect = $this->get(route('auth.telegram.redirect'))->headers->get('Location');
    $query = [];

    parse_str((string) parse_url((string) $redirect, PHP_URL_QUERY), $query);

    expect($query['client_id'])->toBe('test-client')
        ->and($query['response_type'])->toBe('code')
        ->and($query['scope'])->toBe('openid profile')
        ->and($query['redirect_uri'])->toBe(url('/auth/telegram/callback'))
        ->and($query['code_challenge_method'])->toBe('S256')
        ->and($query['code_challenge'])->toBe(match ($verifier = (string) session('telegram_code_verifier')) {
            '' => null,
            default => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        })
        ->and($query['state'])->toBe(session('telegram_oauth_state'));
});

test('telegram callback creates and authenticates a new user', function () {
    $state = telegramState();

    fakeTelegramOauth([
        'iss' => 'https://oauth.telegram.org',
        'aud' => 'test-client',
        'sub' => '987654321',
        'iat' => now()->timestamp,
        'exp' => now()->addHour()->timestamp,
        'name' => 'Aziz Karimov',
        'preferred_username' => 'aziz',
        'picture' => 'https://t.me/i/userpic/320/aziz.jpg',
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('valuation'));

    $this->assertAuthenticated();

    $user = User::query()->where('telegram_id', '987654321')->firstOrFail();

    expect($user->name)->toBe('Aziz Karimov')
        ->and($user->telegram_username)->toBe('aziz')
        ->and($user->avatar)->toBe('https://t.me/i/userpic/320/aziz.jpg')
        ->and($user->email)->toBeNull();

    Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'oauth.telegram.org/token')
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-client:test-secret'))
        && $request['code_verifier'] !== null
        && $request['code'] === 'valid-code');
});

test('telegram callback refreshes the profile of a returning user', function () {
    $existing = User::factory()->create([
        'email' => null,
        'telegram_id' => '987654321',
        'telegram_username' => 'old',
        'avatar' => 'https://t.me/old.jpg',
    ]);

    $state = telegramState();

    fakeTelegramOauth([
        'iss' => 'https://oauth.telegram.org',
        'aud' => 'test-client',
        'sub' => '987654321',
        'iat' => now()->timestamp,
        'exp' => now()->addHour()->timestamp,
        'name' => 'Aziz Karimov',
        'preferred_username' => 'aziz',
        'picture' => 'https://t.me/new.jpg',
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('valuation'));

    $this->assertAuthenticatedAs($existing);

    expect($existing->refresh()->telegram_username)->toBe('aziz')
        ->and($existing->avatar)->toBe('https://t.me/new.jpg')
        ->and(User::query()->count())->toBe(1);
});

test('telegram callback redirects to login when the user denies access', function () {
    telegramState();

    $this->get(route('auth.telegram.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('telegram callback redirects to login when the state does not match', function () {
    telegramState();

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => 'boshqa-state']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('telegram callback redirects to login when the token exchange fails', function () {
    $state = telegramState();

    Http::preventStrayRequests();
    Http::fake(['*oauth.telegram.org/token*' => Http::response(['error' => 'invalid_grant'], 400)]);

    $this->get(route('auth.telegram.callback', ['code' => 'eski-kod', 'state' => $state]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('telegram callback rejects a token issued for another client', function () {
    $state = telegramState();

    fakeTelegramOauth([
        'iss' => 'https://oauth.telegram.org',
        'aud' => 'boshqa-client',
        'sub' => '987654321',
        'iat' => now()->timestamp,
        'exp' => now()->addHour()->timestamp,
        'name' => 'Aziz Karimov',
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('telegram callback rejects an expired token', function () {
    $state = telegramState();

    fakeTelegramOauth([
        'iss' => 'https://oauth.telegram.org',
        'aud' => 'test-client',
        'sub' => '987654321',
        'iat' => now()->subHours(2)->timestamp,
        'exp' => now()->subHour()->timestamp,
        'name' => 'Aziz Karimov',
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('telegram callback copies the profile photo to local storage', function () {
    Storage::fake('avatars');

    $state = telegramState();

    [$secret, $jwks] = telegramSigningKey();

    Http::preventStrayRequests();
    Http::fake([
        '*oauth.telegram.org/token*' => Http::response([
            'access_token' => 'telegram-access-token',
            'expires_in' => 3600,
            'id_token' => JWT::encode([
                'iss' => 'https://oauth.telegram.org',
                'aud' => 'test-client',
                'sub' => '987654321',
                'iat' => now()->timestamp,
                'exp' => now()->addHour()->timestamp,
                'name' => 'Aziz Karimov',
                'preferred_username' => 'aziz',
                'picture' => 'https://t.me/i/userpic/320/aziz.jpg',
            ], $secret, 'HS256', 'test-key'),
        ]),
        '*oauth.telegram.org/.well-known/jwks.json*' => Http::response($jwks),
        'https://t.me/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('valuation'));

    $user = User::query()->where('telegram_id', '987654321')->firstOrFail();

    expect($user->avatar)->toBe('/avatars/telegram-'.$user->id.'.jpg');

    Storage::disk('avatars')->assertExists('telegram-'.$user->id.'.jpg');
});

test('navbar renders the local avatar of a telegram user', function () {
    $user = User::factory()->create(['avatar' => '/avatars/telegram-99.jpg']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('/avatars/telegram-99.jpg');
});

test('telegram callback rejects a token signed by an unknown key', function () {
    $state = telegramState();

    [, $jwks] = telegramSigningKey();

    Http::preventStrayRequests();
    Http::fake([
        '*oauth.telegram.org/token*' => Http::response([
            'access_token' => 'telegram-access-token',
            'expires_in' => 3600,
            'id_token' => JWT::encode([
                'iss' => 'https://oauth.telegram.org',
                'aud' => 'test-client',
                'sub' => '987654321',
                'iat' => now()->timestamp,
                'exp' => now()->addHour()->timestamp,
                'name' => 'Aziz Karimov',
            ], 'boshqa-maxfiy-kalit-ham-32-baytdan-uzun-bolsin!', 'HS256', 'test-key'),
        ]),
        '*oauth.telegram.org/.well-known/jwks.json*' => Http::response($jwks),
    ]);

    $this->get(route('auth.telegram.callback', ['code' => 'valid-code', 'state' => $state]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});
