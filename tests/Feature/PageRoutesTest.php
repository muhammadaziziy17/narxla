<?php

use App\Models\User;
use App\Models\Valuation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('landing page loads successfully and displays cached statistics from real db', function () {
    Valuation::factory()->for(User::factory())->count(5)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Narxni aniqlash')
        ->assertSee('Baholangan qurilma')
        ->assertSee('data-count="5"', false)
        ->assertDontSee('12000')
        ->assertDontSee('12 000')
        ->assertDontSee('fa-brands fa-telegram');
});

test('login page loads successfully', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('auth.google.redirect'))
        ->assertSee(route('auth.telegram.redirect'));
});

test('valuation page loads successfully', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('valuation'))
        ->assertOk()
        ->assertSee('AI tahlil qilmoqda')
        ->assertSee('Narxni aniqlash');
});

test('valuations history page renders custom minimalist pagination when paginated', function () {
    $user = User::factory()->create();

    Valuation::factory()->count(15)->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('valuations.index'))
        ->assertOk()
        ->assertSee('Jami')
        ->assertSee("ko'rsatilmoqda", false);
});
