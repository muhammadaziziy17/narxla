<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TelegramAuthController extends Controller
{
    /**
     * Telegram OpenID Connect manzillari (Response type: code, PKCE: S256).
     */
    private const AUTHORIZE_URL = 'https://oauth.telegram.org/auth';

    private const TOKEN_URL = 'https://oauth.telegram.org/token';

    private const JWKS_URL = 'https://oauth.telegram.org/.well-known/jwks.json';

    private const ISSUER = 'https://oauth.telegram.org';

    private const SCOPES = 'openid profile';

    /**
     * Foydalanuvchini Telegram'ning rozilik sahifasiga yo'naltirish.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = (string) config('services.telegram.client_id');

        if ($clientId === '') {
            return $this->failed('Telegram login hali sozlanmagan.');
        }

        $state = Str::random(40);
        $codeVerifier = Str::random(96);

        $request->session()->put('telegram_oauth_state', $state);
        $request->session()->put('telegram_code_verifier', $codeVerifier);

        return redirect()->away(self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ]));
    }

    /**
     * Telegram'dan qaytgan kodni tekshirib, foydalanuvchini tizimga kiritish.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->failed('Telegram orqali kirish bekor qilindi.');
        }

        $state = (string) $request->session()->pull('telegram_oauth_state');
        $codeVerifier = (string) $request->session()->pull('telegram_code_verifier');
        $code = (string) $request->input('code');

        if ($code === '' || $state === '' || ! hash_equals($state, (string) $request->input('state'))) {
            logger()->warning('Telegram OAuth: state mos kelmadi.', [
                'host' => $request->getHost(),
                'redirect' => config('services.telegram.redirect'),
            ]);

            return $this->failed("Telegram oqimi sessiyaga bog'lanmadi. Sahifani yangilab, qaytadan urinib ko'ring.");
        }

        try {
            $claims = $this->exchangeCodeForClaims($code, $codeVerifier);
        } catch (ExpiredException $exception) {
            // Odatda sabab — soat farqi: token "eskirgan" deb hisoblanadi.
            logger()->warning("Telegram id_token muddati o'tgan.", [
                'exp' => $exception->getPayload()?->exp,
                'iat' => $exception->getPayload()?->iat,
                'server_time' => $exception->getTimestamp(),
            ]);

            return $this->failed("Telegram oqimi eskirib qoldi. Qaytadan urinib ko'ring.");
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed("Telegram orqali kirish amalga oshmadi. Qaytadan urinib ko'ring.");
        }

        $telegramId = (string) ($claims['sub'] ?? '');

        if ($telegramId === '') {
            return $this->failed("Telegram hisobingizdan foydalanuvchi ID sini olib bo'lmadi.");
        }

        $user = $this->upsertUser($claims, $telegramId);

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('valuation'));
    }

    /**
     * Kodni tokenlarga almashtirish va id_token'ni tekshirish.
     *
     * @return array<string, mixed>
     */
    private function exchangeCodeForClaims(string $code, string $codeVerifier): array
    {
        $clientId = (string) config('services.telegram.client_id');
        $clientSecret = (string) config('services.telegram.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Telegram Client ID yoki Client Secret sozlanmagan.');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->acceptJson()
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'client_id' => $clientId,
                'code_verifier' => $codeVerifier,
            ])
            ->throw();

        $idToken = (string) $response->json('id_token', '');

        if ($idToken === '') {
            throw new RuntimeException("Telegram javobida id_token yo'q.");
        }

        $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->jwks(), 'RS256'));

        // Token aynan Telegram'dan kelganiga ishonch hosil qilamiz.
        if (($claims['iss'] ?? null) !== self::ISSUER || (string) ($claims['aud'] ?? '') !== $clientId) {
            throw new RuntimeException("Telegram id_token tekshiruvdan o'tmadi.");
        }

        return $claims;
    }

    /**
     * Telegram'ning ochiq kalitlari (id_token imzosini tekshirish uchun).
     *
     * @return array<string, mixed>
     */
    private function jwks(): array
    {
        return (array) Cache::remember('telegram-jwks', now()->addHours(12), function (): array {
            return (array) Http::acceptJson()->get(self::JWKS_URL)->throw()->json();
        });
    }

    /**
     * Telegram ma'lumotlari asosida foydalanuvchini topish yoki yaratish.
     *
     * @param  array<string, mixed>  $claims
     */
    private function upsertUser(array $claims, string $telegramId): User
    {
        $username = $claims['preferred_username'] ?? null;
        $picture = $claims['picture'] ?? null;

        $user = User::query()->where('telegram_id', $telegramId)->first() ?? new User;

        $user->fill([
            'name' => $claims['name'] ?? ($username ?: 'Telegram foydalanuvchisi'),
            'avatar' => $picture,
            'telegram_id' => $telegramId,
            'telegram_username' => $username,
        ])->save();

        $this->cacheAvatar($user, $picture);

        return $user;
    }

    /**
     * Telegram'dagi profil rasmini nusxalab, lokal manzilga o'tkazadi.
     *
     * Telegram bergan rasm manzili qisqa muddatli bo'ladi — shuning uchun
     * kirish paytida nusxasini saqlab qolamiz. Aks holda navbar bosh harfga qaytadi.
     */
    private function cacheAvatar(User $user, ?string $picture): void
    {
        if (empty($picture)) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($picture);

            $contentType = (string) $response->header('Content-Type');

            if (! $response->successful() || ! str_starts_with($contentType, 'image/')) {
                return;
            }

            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $disk = Storage::disk('avatars');

            // Eski nusxalarni tozalaymiz — kengaytmasi o'zgargan bo'lishi mumkin.
            foreach (['jpg', 'png', 'webp', 'gif'] as $oldExtension) {
                $disk->delete('telegram-'.$user->id.'.'.$oldExtension);
            }

            $filename = 'telegram-'.$user->id.'.'.$extension;

            if (! $disk->put($filename, $response->body())) {
                return;
            }

            $user->fill(['avatar' => '/avatars/'.$filename])->save();
        } catch (Throwable $exception) {
            // Rasm olinmasa — asl manzil qoladi (navbar bosh harfga qaytadi).
            logger()->warning('Telegram avatar nusxalanmadi.', ['user_id' => $user->id]);
        }
    }

    /**
     * Ruxsat etilgan URL'larga mos keladigan callback manzili.
     */
    private function redirectUri(): string
    {
        $redirect = (string) config('services.telegram.redirect');

        return Str::startsWith($redirect, '/') ? url($redirect) : $redirect;
    }

    /**
     * PKCE uchun S256 code challenge.
     */
    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Oqim uzilganda login sahifasiga xabar bilan qaytarish.
     */
    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('auth_error', $message);
    }
}
