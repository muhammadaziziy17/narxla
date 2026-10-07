<?php

use App\Models\User;
use App\Models\ValuationRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Bozorda e'lon topilmagan holatni soxtalashtiradi.
 */
function fakeNoMarketListings(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/*' => Http::response(['data' => []], 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
    ]);
}

test('a phone outside the catalog is stored as a request', function () {
    fakeNoMarketListings();

    $this->postJson(route('valuations.store'), [
        'description' => 'Vertu Signature, 64 GB, ideal holat',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'requested')
        ->assertJsonPath('message', "Bu model bo'yicha yetarli e'lon topilmadi. So'rovingiz qabul qilindi — mutaxassis narxni aniqlab, siz bilan bog'lanadi.");

    $valuationRequest = ValuationRequest::query()->firstOrFail();

    expect($valuationRequest->user_id)->toBe(auth()->id())
        ->and($valuationRequest->description)->toBe('Vertu Signature, 64 GB, ideal holat')
        ->and($valuationRequest->status)->toBe('new');
});

test('a request from an authenticated user is linked to the account', function () {
    fakeNoMarketListings();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('valuations.store'), ['description' => 'Vertu Signature, 64 GB'])
        ->assertOk()
        ->assertJsonPath('status', 'requested');

    expect(ValuationRequest::query()->firstOrFail()->user_id)->toBe($user->id);
});

test('the admin is notified on telegram when it is configured', function () {
    config([
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.admin_chat_id' => '123456',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/*' => Http::response(['data' => []], 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
        'https://api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    $this->postJson(route('valuations.store'), [
        'description' => 'Vertu Signature, 64 GB',
    ])->assertOk();

    Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'api.telegram.org/bottest-bot-token/sendMessage')
        && $request['chat_id'] === '123456'
        && str_contains((string) $request['text'], 'Vertu Signature'));
});

test('no telegram message is sent when it is not configured', function () {
    config([
        'services.telegram.bot_token' => null,
        'services.telegram.admin_chat_id' => null,
    ]);

    fakeNoMarketListings();

    $this->postJson(route('valuations.store'), [
        'description' => 'Vertu Signature, 64 GB',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'requested');

    Http::assertNotSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'api.telegram.org'));
});
