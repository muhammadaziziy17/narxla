<?php

use App\Models\User;
use App\Models\Valuation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * BirBir sahifasidagi namuna e'lon havolasi.
 */
function birbirListingUrl(): string
{
    return 'https://birbir.uz/uz/toshkent/cat/telefonlar/smartfonlar/o/iphone-15-318675804';
}

/**
 * BirBir qidiruv sahifasining markdown javobi (Jina Reader ko'rinishi).
 */
function birbirMarkdown(): string
{
    $url = birbirListingUrl();

    return <<<MD
        Title: E'lonlar taxtasi - BirBir

        Markdown Content:
        ![Image 1: iPhone 15](https://cdn-img.birbir.uz/i/400x400-fit/files/x.jpg)

        [](https://birbir.uz/uz/similar/318675804)

        5 000 000 so'm

        ## iPhone 15 128 GB

        Toshkent

        04.10.2026, 15:50

        [iPhone 15 128 GB]({$url})

        PRO
        MD;
}

/**
 * OLX e'loni (API ko'rinishida).
 *
 * @return array<string, mixed>
 */
function olxOffer(string $url, string $title, int $price): array
{
    return [
        'title' => $title,
        'url' => $url,
        'description' => $title.', batareya 90%, ideal holat',
        'params' => [
            ['key' => 'price', 'value' => ['converted_value' => $price, 'currency' => 'UZS']],
            ['key' => 'state', 'value' => ['key' => 'used']],
        ],
    ];
}

/**
 * OLX'dagi namuna e'lonlar.
 *
 * @return array<int, array<string, mixed>>
 */
function olxOffers(): array
{
    return [
        olxOffer('https://olx.uz/d/1', 'iPhone 15 128GB ideal', 6000000),
        olxOffer('https://olx.uz/d/2', 'iPhone 15 128 GB', 5500000),
        olxOffer('https://olx.uz/d/3', 'iPhone 15 128gb sotiladi', 5800000),
    ];
}

/**
 * Model tahlilining namuna javobi.
 *
 * @param  array<int, array<string, mixed>>|null  $comparables
 * @return array<string, mixed>
 */
function marketAnalysis(?array $comparables = null, string $deviceType = 'phone'): array
{
    return [
        'device_type' => $deviceType,
        'brand' => 'Apple',
        'model' => 'iPhone 15',
        'storage_gb' => 128,
        'battery' => 90,
        'condition' => 'good',
        'comparables' => $comparables ?? [
            ['title' => 'iPhone 15 128GB ideal', 'url' => 'https://olx.uz/d/1', 'price' => 6000000, 'currency' => 'UZS'],
            ['title' => 'iPhone 15 128 GB', 'url' => 'https://olx.uz/d/2', 'price' => 5500000, 'currency' => 'UZS'],
            ['title' => 'iPhone 15 128 GB', 'url' => birbirListingUrl(), 'price' => 5000000, 'currency' => 'UZS'],
        ],
        'insight' => "Topilgan e'lonlar asosida narx aniqlandi. Narx oralig'i 5–6 mln so'm atrofida.",
    ];
}

/**
 * OLX, BirBir, Tavily va Cloudflare javoblarini soxtalashtiradi.
 *
 * @param  array<string, mixed>|null  $analysis
 * @param  array<int, array<string, mixed>>|null  $offers
 */
function fakeMarket(?array $analysis = null, ?array $offers = null, ?string $birbir = null): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/https://www.olx.uz/*' => Http::response(['data' => $offers ?? olxOffers()], 200),
        'https://r.jina.ai/https://birbir.uz/*' => Http::response($birbir ?? birbirMarkdown(), 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
        'https://api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => [
                'choices' => [
                    ['message' => ['content' => json_encode($analysis ?? marketAnalysis())]],
                ],
            ],
        ], 200),
    ]);
}

test('olx and birbir listings produce a som price range with sources', function () {
    fakeMarket();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB, batareya 90%'])
        ->assertOk()
        ->assertJsonPath('status', 'estimated')
        ->assertJsonPath('result.price_source', 'market')
        ->assertJsonPath('result.source_label', 'Jonli bozor tahlili')
        ->assertJsonPath('result.range', "5 000 000 – 6 000 000 so'm")
        ->assertJsonPath('result.range_usd', '$396 – $476')
        ->assertJsonPath('result.confidence', 67)
        ->assertJsonPath('result.sources.0.url', 'https://olx.uz/d/1')
        ->assertJsonPath('result.sources.0.price', "6 000 000 so'm")
        ->assertJsonPath('result.sources.0.source', 'olx')
        ->assertJsonPath('result.sources.0.label', 'OLX')
        ->assertJsonPath('result.sources.0.favicon', 'https://www.google.com/s2/favicons?sz=64&domain=olx.uz')
        ->assertJsonPath('result.device', 'Apple iPhone 15 · 128 GB');
});

test('birbir listings are parsed with their own emblem', function () {
    fakeMarket();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.sources.2.url', birbirListingUrl())
        ->assertJsonPath('result.sources.2.source', 'birbir')
        ->assertJsonPath('result.sources.2.label', 'BirBir')
        ->assertJsonPath('result.sources.2.short', 'BB')
        ->assertJsonPath('result.sources.2.price', "5 000 000 so'm")
        ->assertJsonPath('result.sources.2.favicon', 'https://www.google.com/s2/favicons?sz=64&domain=birbir.uz');
});

test('the insight explains the sources and the number of listings', function () {
    fakeMarket();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.insight', fn (string $insight): bool => str_contains($insight, "OLX va BirBir manbalaridagi 3 ta mos e'lon")
            && str_contains($insight, "5 000 000 – 6 000 000 so'm oralig'ida"));
});

test('price sentences are stripped from the model insight', function () {
    fakeMarket();

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.insight', fn (string $insight): bool => str_contains($insight, "Topilgan e'lonlar asosida narx aniqlandi.")
            && ! str_contains($insight, 'atrofida')
            && str_contains($insight, "manbalaridagi 3 ta mos e'lon"));
});

test('dollar listings from olx are converted to som', function () {
    $offers = array_map(function (array $offer): array {
        $offer['params'][0]['value'] = ['value' => 500, 'currency' => 'UYE'];

        return $offer;
    }, olxOffers());

    fakeMarket(
        marketAnalysis([
            ['title' => 'iPhone 15', 'url' => 'https://olx.uz/d/1', 'price' => 500, 'currency' => 'UYE'],
            ['title' => 'iPhone 15', 'url' => 'https://olx.uz/d/2', 'price' => 500, 'currency' => 'UYE'],
            ['title' => 'iPhone 15', 'url' => 'https://olx.uz/d/3', 'price' => 500, 'currency' => 'UYE'],
        ]),
        $offers,
        '',
    );

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'market')
        ->assertJsonPath('result.range', "6 300 000 – 6 300 000 so'm");
});

test('new phone offers are ignored', function () {
    $offers = olxOffers();
    $offers[0]['params'][1]['value'] = ['key' => 'new'];

    fakeMarket(offers: $offers);

    // Yangi (6 mln) e'lon tashlandi — qolgan e'lonlar asosida oraliq tuziladi.
    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'market')
        ->assertJsonPath('result.range', "5 000 000 – 5 500 000 so'm")
        ->assertJsonPath('result.sources.0.url', 'https://olx.uz/d/2');
});

test('listings invented by the model are ignored', function () {
    fakeMarket(marketAnalysis([
        ['title' => 'iPhone 15', 'url' => 'https://olx.uz/d/1', 'price' => 6000000, 'currency' => 'UZS'],
        ['title' => 'iPhone 15', 'url' => 'https://soxta-sayt.uz/e/9', 'price' => 1000000, 'currency' => 'UZS'],
    ]));

    // Faqat 1 ta haqiqiy manba qoldi — yetarli emas, katalog narxi ishlatiladi.
    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'catalog');
});

test('a non-device description is not valued', function () {
    fakeMarket(marketAnalysis(deviceType: 'other'));

    $this->postJson(route('valuations.store'), ['description' => 'Mushuk, 2 yoshli, Toshkent'])
        ->assertOk()
        ->assertJsonPath('status', 'unsupported')
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'telefon, planshet va noutbuk'));
});

test('laptops are valued from market listings', function () {
    fakeMarket(
        [
            'device_type' => 'laptop',
            'brand' => 'Apple',
            'model' => 'MacBook Air M2',
            'storage_gb' => 256,
            'battery' => 90,
            'condition' => 'good',
            'comparables' => [
                ['title' => 'MacBook Air M2 256GB', 'url' => 'https://olx.uz/d/5', 'price' => 9000000, 'currency' => 'UZS'],
                ['title' => 'MacBook Air M2', 'url' => 'https://olx.uz/d/6', 'price' => 9500000, 'currency' => 'UZS'],
                ['title' => 'MacBook Air M2 2022', 'url' => 'https://olx.uz/d/7', 'price' => 9200000, 'currency' => 'UZS'],
            ],
            'insight' => "MacBook Air M2 e'lonlari topildi.",
        ],
        [
            olxOffer('https://olx.uz/d/5', 'MacBook Air M2 256GB', 9000000),
            olxOffer('https://olx.uz/d/6', 'MacBook Air M2', 9500000),
            olxOffer('https://olx.uz/d/7', 'MacBook Air M2 2022', 9200000),
        ],
    );

    $this->postJson(route('valuations.store'), ['description' => 'MacBook Air M2, 256 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'market')
        ->assertJsonPath('result.device', 'Apple MacBook Air M2 · 256 GB')
        ->assertJsonPath('result.range', "9 000 000 – 9 500 000 so'm");
});

test('the monthly search budget stops tavily searches', function () {
    Cache::put('tavily-searches-'.now()->format('Y-m'), 99999, now()->addMonths(2));

    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/*' => Http::response(['data' => []], 200),
        'https://api.tavily.com/*' => Http::response(['results' => olxOffers()], 200),
    ]);

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.price_source', 'catalog');

    Http::assertNotSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'api.tavily.com'));
});

test('an unknown phone with no market listings is sent to the expert', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/*' => Http::response(['data' => []], 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
    ]);

    $this->postJson(route('valuations.store'), [
        'description' => 'Vertu Signature, 64 GB, ideal holat',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'requested');
});

test('a market valuation is saved together with its sources', function () {
    $user = User::factory()->create();

    fakeMarket();

    $this->actingAs($user)
        ->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('saved', true);

    $valuation = Valuation::query()->firstOrFail();

    expect($valuation->user_id)->toBe($user->id)
        ->and($valuation->price_source)->toBe('market')
        ->and($valuation->price_low_uzs)->toBe(5000000)
        ->and($valuation->price_high_uzs)->toBe(6000000)
        ->and($valuation->sources)->toHaveCount(3)
        ->and($valuation->sources[2]['source'])->toBe('birbir')
        ->and($valuation->checked_at)->not->toBeNull();
});

test('history shows live market sources with emblems', function () {
    $user = User::factory()->create();

    Valuation::factory()->for($user)->create([
        'brand' => 'Apple',
        'model' => 'iPhone 15',
        'price_source' => 'market',
        'price_low_uzs' => 5000000,
        'price_high_uzs' => 6000000,
        'sources' => [
            ['title' => 'iPhone 15 128GB', 'url' => 'https://olx.uz/d/1', 'price_uzs' => 6000000, 'source' => 'olx'],
            ['title' => 'iPhone 15 128 GB', 'url' => birbirListingUrl(), 'price_uzs' => 5000000, 'source' => 'birbir'],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('valuations.index'))
        ->assertOk()
        ->assertSee('Jonli bozor tahlili')
        ->assertSee("5 000 000 – 6 000 000 so'm")
        ->assertSee('https://olx.uz/d/1')
        ->assertSee('domain=birbir.uz');
});

test('market valuation returns recommendation and mandatory disclaimer', function () {
    fakeMarket(array_merge(marketAnalysis(), [
        'recommendation' => [
            'action' => 'sell_now',
            'badge' => 'Hozir sotish tavsiya etiladi',
            'reason' => 'Bozorda talab yuqori va narxlar eng yaxshi choqqisida.',
            'disclaimer' => "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.",
        ],
    ]));

    $this->postJson(route('valuations.store'), ['description' => 'iPhone 15, 128 GB'])
        ->assertOk()
        ->assertJsonPath('result.recommendation.action', 'sell_now')
        ->assertJsonPath('result.recommendation.badge', 'Hozir sotish tavsiya etiladi')
        ->assertJsonPath('result.recommendation.reason', 'Bozorda talab yuqori va narxlar eng yaxshi choqqisida.')
        ->assertJsonPath('result.recommendation.disclaimer', "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.");
});
