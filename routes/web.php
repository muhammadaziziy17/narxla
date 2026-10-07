<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\TelegramAuthController;
use App\Http\Controllers\ValuationController;
use App\Models\Valuation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stats = Cache::remember('landing_stats', now()->addHour(), function () {
        try {
            $dbCount = Valuation::count();
            $avgConfidence = Valuation::avg('confidence');

            return [
                'devices_count' => $dbCount,
                'analysis_seconds' => 10,
                'accuracy_percent' => $avgConfidence ? (int) round($avgConfidence) : 98,
            ];
        } catch (Throwable) {
            return [
                'devices_count' => 0,
                'analysis_seconds' => 10,
                'accuracy_percent' => 98,
            ];
        }
    });

    return view('landing', ['stats' => $stats]);
})->name('home');

Route::middleware('throttle:auth')->group(function () {
    Route::get('/login', function () {
        if (Auth::check()) {
            return redirect()->route('valuation');
        }

        return view('auth.login');
    })->name('login');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    Route::get('/auth/telegram/redirect', [TelegramAuthController::class, 'redirect'])->name('auth.telegram.redirect');
    Route::get('/auth/telegram/callback', [TelegramAuthController::class, 'callback'])->name('auth.telegram.callback');
});

Route::get('/valuation', [ValuationController::class, 'show'])
    ->middleware('auth')
    ->name('valuation');

Route::post('/valuations', [ValuationController::class, 'store'])
    ->middleware(['auth', 'throttle:valuations'])
    ->name('valuations.store');

Route::get('/valuations', [ValuationController::class, 'index'])
    ->middleware('auth')
    ->name('valuations.index');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');
