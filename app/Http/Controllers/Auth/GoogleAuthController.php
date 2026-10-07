<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Foydalanuvchini Google'ning rozilik sahifasiga yo'naltirish.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google'dan qaytgan javobni qayta ishlash va foydalanuvchini tizimga kiritish.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->failed('Google orqali kirish bekor qilindi.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            if ($exception instanceof InvalidStateException) {
                // Oqim boshqa domenda boshlangan yoki sessiya eskirgan bo'lishi mumkin —
                // keyingi safar tez topish uchun domendagi nomuvofiqlikni logga yozamiz.
                logger()->warning('Google OAuth: state mos kelmadi.', [
                    'host' => $request->getHost(),
                    'redirect' => config('services.google.redirect'),
                ]);
            } else {
                report($exception);
            }

            return $this->failed("Google oqimi sessiyaga bog'lanmadi. Sahifani yangilab, qaytadan urinib ko'ring.");
        }

        $email = $googleUser->getEmail();

        if (empty($email)) {
            return $this->failed("Google akkauntingizdan email manzilini olib bo'lmadi.");
        }

        $user = $this->upsertUser($googleUser, $email);

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('valuation'));
    }

    /**
     * Google ma'lumotlari asosida foydalanuvchini topish yoki yaratish.
     */
    private function upsertUser(SocialiteUser $googleUser, string $email): User
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $email)->first()
            ?? new User(['email' => $email]);

        $user->fill([
            'name' => $googleUser->getName() ?: Str::before($email, '@'),
            'avatar' => $googleUser->getAvatar(),
            'google_id' => $googleUser->getId(),
        ]);

        // Google email manzilni tasdiqlagan hisoblanadi. Bu maydon fillable'da
        // yo'q — shuning uchun to'g'ridan-to'g'ri o'rnatiladi.
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    /**
     * Oqim uzilganda login sahifasiga xabar bilan qaytarish.
     */
    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('auth_error', $message);
    }
}
