<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(LazilyRefreshDatabase::class);

test('google login redirects the user to the authorization screen', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect('https://socialite.fake/google/authorize');
});

test('google callback creates and authenticates a new user', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-100',
        'name' => 'Aziz Karimov',
        'email' => 'aziz@example.com',
        'avatar' => 'https://example.com/aziz.jpg',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('valuation'));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'aziz@example.com')->firstOrFail();

    expect($user->name)->toBe('Aziz Karimov')
        ->and($user->google_id)->toBe('google-100')
        ->and($user->avatar)->toBe('https://example.com/aziz.jpg')
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('google callback links an existing email account and verifies it', function () {
    $user = User::factory()->unverified()->create(['email' => 'aziz@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-200',
        'name' => 'Aziz',
        'email' => 'aziz@example.com',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('valuation'));

    expect($user->refresh()->google_id)->toBe('google-200')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(User::query()->count())->toBe(1);
});

test('google callback refreshes the profile of a returning user', function () {
    $existing = User::factory()->create([
        'email' => 'aziz@example.com',
        'google_id' => 'google-300',
        'avatar' => 'https://example.com/old.jpg',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-300',
        'name' => 'Aziz Karimov',
        'email' => 'aziz@example.com',
        'avatar' => 'https://example.com/new.jpg',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('valuation'));

    $this->assertAuthenticatedAs($existing);

    expect($existing->refresh()->avatar)->toBe('https://example.com/new.jpg')
        ->and(User::query()->count())->toBe(1);
});

test('google callback redirects to login when the user denies access', function () {
    $this->get(route('auth.google.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('google callback redirects to login when the flow is broken', function () {
    Socialite::fake('google', fn () => throw new InvalidStateException);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('google callback redirects to login when the account has no email', function () {
    Socialite::fake('google', SocialiteUser::fake(['email' => null]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_error');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('login page shows the authentication error message', function () {
    $this->withSession(['auth_error' => 'Google orqali kirish amalga oshmadi.'])
        ->get(route('login'))
        ->assertOk()
        ->assertSee('Google orqali kirish amalga oshmadi.');
});

test('login page redirects an authenticated user to valuation', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('valuation'));
});

test('logout requires authentication', function () {
    $this->post(route('logout'))
        ->assertRedirect(route('login'));
});

test('logout ends the session and redirects home', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('home page shows the signed in user and a logout action', function () {
    $user = User::factory()->create(['name' => 'Aziz Karimov']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Aziz Karimov')
        ->assertSee(route('logout'));
});

test('home page shows the login action for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('login'));
});
