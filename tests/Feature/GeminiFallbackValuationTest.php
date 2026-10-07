<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function geminiAnalysisPayload(): array
{
    return [
        'device_type' => 'phone',
        'brand' => 'Apple',
        'model' => 'iPhone 15',
        'storage_gb' => 128,
        'battery' => 90,
        'condition' => 'good',
        'comparables' => [
            ['title' => 'iPhone 15 128GB ideal', 'url' => 'https://olx.uz/d/1', 'price' => 6000000, 'currency' => 'UZS'],
            ['title' => 'iPhone 15 128 GB', 'url' => 'https://olx.uz/d/2', 'price' => 5500000, 'currency' => 'UZS'],
        ],
        'insight' => 'Gemini 3.8 Flash orqali e\'lonlar tahlil qilindi.',
        'recommendation' => [
            'action' => 'sell_now',
            'badge' => 'Hozir sotish tavsiya etiladi',
            'reason' => 'Bozorda talab yuqori.',
        ],
    ];
}

test('system automatically falls back to gemini when cloudflare fails', function () {
    Config::set('services.gemini.api_key', 'test-gemini-key');
    Config::set('services.gemini.model', 'gemini-3.8-flash');

    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/https://www.olx.uz/*' => Http::response([
            'data' => [
                ['title' => 'iPhone 15 128GB ideal', 'url' => 'https://olx.uz/d/1', 'params' => [['key' => 'price', 'value' => ['converted_value' => 6000000, 'currency' => 'UZS']]]],
                ['title' => 'iPhone 15 128 GB', 'url' => 'https://olx.uz/d/2', 'params' => [['key' => 'price', 'value' => ['converted_value' => 5500000, 'currency' => 'UZS']]]],
            ],
        ], 200),
        'https://r.jina.ai/https://birbir.uz/*' => Http::response('', 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
        // Cloudflare returns server error
        'https://api.cloudflare.com/*' => Http::response(['success' => false], 500),
        // Gemini successfully responds
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode(geminiAnalysisPayload())],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson(route('valuations.store'), [
        'description' => 'iPhone 15 128GB',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'estimated')
        ->assertJsonPath('result.price_source', 'market')
        ->assertJsonPath('result.recommendation.action', 'sell_now');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'generativelanguage.googleapis.com')
            && str_contains($request->url(), 'gemini-3.8-flash');
    });
});

test('system uses gemini when cloudflare credentials are empty', function () {
    Config::set('services.cloudflare.account_id', '');
    Config::set('services.cloudflare.api_token', '');
    Config::set('services.gemini.api_key', 'test-gemini-key');
    Config::set('services.gemini.model', 'gemini-3.8-flash');

    Http::preventStrayRequests();
    Http::fake([
        'https://r.jina.ai/https://www.olx.uz/*' => Http::response([
            'data' => [
                ['title' => 'iPhone 15 128GB ideal', 'url' => 'https://olx.uz/d/1', 'params' => [['key' => 'price', 'value' => ['converted_value' => 6000000, 'currency' => 'UZS']]]],
                ['title' => 'iPhone 15 128 GB', 'url' => 'https://olx.uz/d/2', 'params' => [['key' => 'price', 'value' => ['converted_value' => 5500000, 'currency' => 'UZS']]]],
            ],
        ], 200),
        'https://r.jina.ai/https://birbir.uz/*' => Http::response('', 200),
        'https://api.tavily.com/*' => Http::response(['results' => []], 200),
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode(geminiAnalysisPayload())],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson(route('valuations.store'), [
        'description' => 'iPhone 15 128GB',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'estimated')
        ->assertJsonPath('result.price_source', 'market');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.cloudflare.com'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
});

test('valuations route is rate limited after 10 requests', function () {
    Http::fake();

    for ($i = 0; $i < 10; $i++) {
        $response = $this->postJson(route('valuations.store'), [
            'description' => 'Samsung S24 Ultra',
        ]);
        $response->assertStatus(200);
    }

    // 11th request should be throttled (429)
    $response = $this->postJson(route('valuations.store'), [
        'description' => 'Samsung S24 Ultra',
    ]);

    $response->assertStatus(429);
    $response->assertJson([
        'message' => "Juda ko'p so'rov yuborildi. Iltimos, 1 daqiqadan so'ng qayta urinib ko'ring.",
    ]);
});
