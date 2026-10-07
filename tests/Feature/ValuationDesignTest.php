<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the valuation page renders centered without design switcher', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('valuation'))
        ->assertOk()
        ->assertDontSee('design-switcher')
        ->assertDontSee('data-design')
        ->assertSee('valuation-modal')
        ->assertSee('modal-backdrop')
        ->assertSee('is-compact-view')
        ->assertSee('loader-modal-content')
        ->assertSee('error-modal-content')
        ->assertSee('Bozor maslahati')
        ->assertSee('Rasmiy baholash sertifikati')
        ->assertSee('downloadCertificate()');
});

test('the old design route is not registered', function () {
    $this->postJson('/valuation/design', ['design' => 'focus'])
        ->assertNotFound();
});
