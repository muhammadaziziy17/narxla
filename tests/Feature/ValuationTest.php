<?php

use App\Models\User;
use App\Models\Valuation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Jonli bozor qidiruvi mavjud emas holatini soxtalashtiradi — katalog zaxirasi sinaladi.
 */
function fakeMarketUnavailable(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/*' => Http::response(['data' => []], 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
    ]);
}

test('valuation page shows a single description field', function () {
    $this->get(route('valuation'))
        ->assertOk()
        ->assertSee('id="description"', escape: false)
        ->assertSee('Narxni aniqlash');
});

test('guests are redirected to login from the valuation page', function () {
    Auth::logout();

    $this->get(route('valuation'))->assertRedirect(route('login'));
});

test('the catalog price is used when no market listings are found', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), [
        'description' => 'iPhone 15, 128 GB, batareya 90%, yaxshi holat',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'estimated')
        ->assertJsonPath('result.price_source', 'catalog')
        ->assertJsonPath('result.range_usd', '$695 – $760')
        ->assertJsonPath('result.range', "8 757 000 – 9 576 000 so'm")
        ->assertJsonPath('result.storage', '128 GB')
        ->assertJsonPath('result.recommendation.action', 'fair_price')
        ->assertJsonPath('result.recommendation.disclaimer', "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.")
        ->assertJsonPath('saved', true);

    expect(Valuation::query()->count())->toBe(1);
});

test('the longest matching model name wins', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), [
        'description' => 'iPhone 15 Pro Max, 128 GB',
    ])
        ->assertOk()
        ->assertJsonPath('result.device', 'Apple iPhone 15 Pro Max · 128 GB')
        ->assertJsonPath('result.range_usd', '$1045 – $1140');
});

test('storage is read from the description', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 256 GB'])
        ->assertOk()
        ->assertJsonPath('result.storage', '256 GB')
        ->assertJsonPath('result.range_usd', '$735 – $805');
});

test('laptops and tablets are supported by the catalog', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), ['description' => 'MacBook Air M2, 256 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'catalog')
        ->assertJsonPath('result.device', 'Apple MacBook Air M2 · 256 GB');

    $this->postJson(route('valuations.store'), ['description' => 'Samsung Galaxy Tab S9, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.device', 'Samsung Galaxy Tab S9 · 128 GB');
});

test('a low battery lowers the price', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, batareya 70%'])
        ->assertOk()
        ->assertJsonPath('result.battery', '70%')
        ->assertJsonPath('result.range_usd', '$645 – $705');
});

test('a scratched condition lowers the price', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, tirnalgan'])
        ->assertOk()
        ->assertJsonPath('result.condition', 'Tirnalgan')
        ->assertJsonPath('result.range_usd', '$615 – $675');
});

test('an authenticated estimate is saved to the account', function () {
    fakeMarketUnavailable();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB, batareya 90%'])
        ->assertOk()
        ->assertJsonPath('status', 'estimated')
        ->assertJsonPath('saved', true)
        ->assertJsonPath('history_url', route('valuations.index'));

    $valuation = Valuation::query()->firstOrFail();

    expect($valuation->user_id)->toBe($user->id)
        ->and($valuation->brand)->toBe('Apple')
        ->and($valuation->model)->toBe('iPhone 15')
        ->and($valuation->storage)->toBe(128)
        ->and($valuation->battery)->toBe(90)
        ->and($valuation->price_source)->toBe('catalog')
        ->and($valuation->price_low)->toBe(695)
        ->and($valuation->price_high)->toBe(760);
});

test('a too short description is rejected', function () {
    fakeMarketUnavailable();

    $this->postJson(route('valuations.store'), ['description' => 'iphone'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');

    expect(Valuation::query()->count())->toBe(0);
});

test('history requires authentication', function () {
    Auth::logout();

    $this->get(route('valuations.index'))
        ->assertRedirect(route('login'));
});

test('history lists only the authenticated user valuations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Valuation::factory()->for($user)->create([
        'brand' => 'Apple',
        'model' => 'iPhone 15',
        'price_low' => 500,
        'price_high' => 600,
        'price_source' => 'catalog',
    ]);

    Valuation::factory()->for($other)->create([
        'brand' => 'Samsung',
        'model' => 'Galaxy S24 Ultra',
    ]);

    $this->actingAs($user)
        ->get(route('valuations.index'))
        ->assertOk()
        ->assertSee('iPhone 15')
        ->assertSee("6 300 000 – 7 560 000 so'm")
        ->assertDontSee('Galaxy S24 Ultra');
});

test('history shows an empty state when the user has no valuations', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('valuations.index'))
        ->assertOk()
        ->assertSee("Hozircha bo'sh", escape: false);
});

test('validation returns friendly Uzbek error message when description is too short', function () {
    $this->postJson(route('valuations.store'), ['description' => 'iPhone'])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'description' => 'Qurilma haqida kamida 10 ta belgi yozing (masalan, model va xotira).',
        ]);
});

test('history shows recommendation badge and reason when available', function () {
    $user = User::factory()->create();

    Valuation::factory()->for($user)->create([
        'brand' => 'Apple',
        'model' => 'iPhone 15',
        'price_low' => 500,
        'price_high' => 600,
        'price_source' => 'catalog',
        'recommendation' => [
            'action' => 'sell_now',
            'badge' => 'Hozir sotish tavsiya etiladi',
            'reason' => 'Bozorda talab yuqori.',
            'disclaimer' => "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.",
        ],
    ]);

    $this->actingAs($user)
        ->get(route('valuations.index'))
        ->assertOk()
        ->assertSee('Hozir sotish tavsiya etiladi')
        ->assertSee('Bozorda talab yuqori.');
});
