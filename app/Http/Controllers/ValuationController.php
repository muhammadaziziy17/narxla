<?php

namespace App\Http\Controllers;

use App\Models\Valuation;
use App\Models\ValuationRequest;
use App\Services\DeviceParser;
use App\Services\SearchQueryNormalizer;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ValuationController extends Controller
{
    /**
     * Jonli bozor tahlilida kamida shu qadar e'lon topilishi kerak.
     * 2 ta haqiqiy e'lon ham (median filtri bilan) ishonchli oraliq beradi.
     */
    private const MIN_COMPARABLES = 2;

    /**
     * Cloudflare modeliga beriladigan tizim ko'rsatmasi.
     */
    private const ANALYST_PROMPT = <<<'PROMPT'
        Sen O'zbekiston qurilmalar bozorini (telefon, planshet, noutbuk) tahlil qiluvchi yordamchisan.
        Senga foydalanuvchi yozgan qurilma tavsifi va internetdan topilgan e'lon natijalari beriladi.

        Vazifang:
        1. Tavsifdan qurilma turini (telefon, planshet yoki noutbuk), modelini, xotira hajmini (GB),
           batareya foizini va holatini aniqlash.
        2. Faqat berilgan e'lon natijalaridan foydalanib, bir xil yoki juda yaqin modeldagi e'lonlarni tanlash.
        3. Har bir tanlangan e'lon uchun uning haqiqiy narxini va valyutasini yozish.

        Qoidalar:
        - Faqat berilgan natijalardagi ma'lumotlardan foydalan. Narxni o'zingdan to'qima.
        - Faqat ishlatilgan (б/у, f/b) qurilma e'lonlarini hisobga ol; yangi qurilma do'konlarini
          (masalan ispace, texnomart, castore, idea) va boshqa model e'lonlarini tashlab ket.
        - Har bir narx aynan foydalanuvchi so'ragan modelga tegishli bo'lishi shart.
        - Agar foydalanuvchi yozgan narsa telefon, planshet yoki noutbuk bo'lmasa (maishiy texnika,
          hayvon, boshqa tovar va h.k.), "device_type" ni "other" qilib qo'y va "comparables" ni bo'sh qoldir.
          Agar foydalanuvchi protsessor yoki noutbuk konfiguratsiyasini yozgan bo'lsa (masalan "core i5 13chi pakalenya", "i7", "ryzen"), buni noutbuk deb hisobla ("device_type": "laptop") va "other" deb belgilama.
        - Batareya foizi yozilmagan bo'lsa, "battery" uchun 90 qo'y.
        - Iloji bo'lsa, kamida 3 ta mos e'lon tanla. Aniq moslik topilmasa, eng yaqin variantlarni
          (xotira hajmi yoki holati boshqacha bo'lsa ham) kirit — bu izohda tushuntiriladi.
        - Iloji bo'lsa, har xil saytlardan (OLX, BirBir) e'lon tanla — ikkala sayt ham ko'rinsin.
        - Mos e'lon topilmasa, uni ro'yxatga qo'shma.
        - "insight" maydonida 2-4 jumla yoz: qanday e'lonlar topilganini, foydalanuvchi so'ragan
          xotira hajmi, holati yoki batareyasi bilan farqlarni (agar bo'lsa) va nima uchun shu
          e'lonlar tanlanganini tushuntir. Yakuniy narx oralig'ini o'zing yozma — u interfeysda
          ko'rsatiladi. Masalan: "Topilgan e'lonlar orasida Galaxy S23 Ultra bor, biroq
          foydalanuvchi so'ragan 1 TB xotira va tirnalgan holatga mos e'lon topilmadi — shuning
          uchun 256 GB variantlar asos qilib olindi."
        - "recommendation" maydonida sotuvchi uchun bozor maslahatini yoz:
          "action": "sell_now" (agar qurilma yuqori talabda bo'lsa yoki tez orada narxi pasayishi kutilsa) YOKI "wait" (agar narx mavsumiy tushgan bo'lsa va kutish manfaatliroq bo'lsa) YOKI "fair_price" (barqaror bozor narxi bo'lsa).
          "badge": Qisqa sarlavha (masalan: "Hozir sotish tavsiya etiladi", "Biroz kutish maqbul", "Bozor narxida sotish mumkin").
          "reason": O'zbekcha 1-2 jumla asosli tushuntirish (nega hozir sotish yoki kutish ma'qul).
          "disclaimer": "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos."
        - Faqat JSON qaytar, boshqa matn yozma.

        JSON formati:
        {
          "device_type": "phone|laptop|tablet|other",
          "brand": "Apple",
          "model": "iPhone 15 Pro Max",
          "storage_gb": 256,
          "battery": 90,
          "condition": "ideal|good|scratched",
          "comparables": [
            {"title": "e'lon sarlavhasi", "url": "https://...", "price": 12345678, "currency": "UZS"}
          ],
          "insight": "O'zbekcha 2-4 jumla izoh",
          "recommendation": {
            "action": "sell_now|wait|fair_price",
            "badge": "Hozir sotish tavsiya etiladi",
            "reason": "Modelga talab yuqori, keyingi avlod chiqishi fonida narx pasayishidan oldin sotish qulay vaqt.",
            "disclaimer": "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos."
          }
        }
        PROMPT;

    public function __construct(
        private readonly DeviceParser $parser,
    ) {}

    /**
     * Foydalanuvchining oldingi baholashlari.
     */
    public function index(Request $request): View
    {
        $valuations = $request->user()
            ->valuations()
            ->latest()
            ->paginate(12);

        return view('valuations.index', [
            'valuations' => $valuations,
            'storages' => config('phones.storages'),
            'conditions' => config('phones.conditions'),
        ]);
    }

    /**
     * Baholash sahifasi.
     */
    public function show(): View
    {
        return view('valuation');
    }

    /**
     * Erkin matndan baholash: jonli bozor tahlili, katalog zaxirasi yoki mutaxassisga so'rov.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'description.required' => "Qurilma haqida ma'lumot kiriting.",
            'description.min' => 'Qurilma haqida kamida 10 ta belgi yozing (masalan, model va xotira).',
            'description.max' => 'Qurilma tavsifi 1000 ta belgidan oshmasligi kerak.',
        ]);

        // Tahlil sekin modelda ishlashi mumkin — PHP vaqt chegarasini uzaytiramiz.
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }

        $description = $validated['description'];

        // 1) Jonli bozor tahlili (OLX + BirBir qidiruvi va AI tahlili).
        $market = $this->marketValuation($description);

        // Qo'llab-quvvatlanmaydigan narsa (maishiy texnika, hayvon va h.k.) — narx qaytarmaymiz.
        if ($market !== null && ($market['device_type'] ?? '') === 'other') {
            return $this->unsupportedResponse();
        }

        if ($market !== null) {
            return $this->respondWithEstimate($request, $market);
        }

        // 2) Zaxira: katalogdagi taxminiy narx (model topilsa).
        $parsed = $this->parse($description);

        if ($parsed !== null) {
            return $this->respondWithEstimate($request, $this->calculate($parsed));
        }

        // 3) Model aniqlanmadi — mutaxassisga so'rov.
        return $this->storeRequest($request, $description);
    }

    /**
     * Natijani saqlash (login qilgan bo'lsa) va javob qaytarish.
     *
     * @param  array<string, mixed>  $valuation
     */
    private function respondWithEstimate(Request $request, array $valuation): JsonResponse
    {
        // Ichki belgilar bazaga saqlanmaydi.
        unset($valuation['device_type']);

        $saved = false;

        if ($user = $request->user()) {
            $user->valuations()->create($valuation);

            $saved = true;
        }

        return response()->json([
            'status' => 'estimated',
            'result' => $this->present($valuation),
            'saved' => $saved,
            'history_url' => $saved ? route('valuations.index') : null,
        ]);
    }

    /**
     * Jonli bozor bahosi: qidiruv natijalari yetarli bo'lsa narx qaytaradi.
     *
     * @return array<string, mixed>|null
     */
    private function marketValuation(string $description): ?array
    {
        // 1) OLX va BirBir'dan to'g'ridan-to'g'ri e'lonlarni olamiz (narxlari bilan).
        $listings = $this->marketListings($description);

        // 2) Natijalar kam bo'lsa — Tavily qidiruvi bilan to'ldiramiz.
        if (count($listings) < self::MIN_COMPARABLES) {
            $tavily = $this->searchListings($description);

            if ($tavily !== null) {
                $listings = array_merge($listings, $tavily);
            }
        }

        if ($listings === []) {
            return null;
        }

        // Tahlil uchun eng mos va xilma-xil e'lonlarni saralab olamiz (modelga haddan tashqari yuklama tushmasligi va timeout bo'lmasligi uchun)
        $selectedListings = $this->selectTopListings($listings);

        return $this->analyzeListings($description, $selectedListings);
    }

    /**
     * Tahlil uchun eng mos va xilma-xil e'lonlarni saralab olish.
     *
     * Haddan tashqari ko'p (40+) e'lon yuborilganda model sekinlashadi va timeout
     * bo'ladi. Har bir manbadan (OLX, BirBir) eng yaxshi 6-8 tadan e'lon tanlab,
     * jami 12-14 ta eng aktual e'lonni modelga uzatamiz.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<int, array<string, mixed>>
     */
    private function selectTopListings(array $listings): array
    {
        if (count($listings) <= 14) {
            return $listings;
        }

        $bySource = [];

        foreach ($listings as $listing) {
            $source = (string) ($listing['source'] ?? 'web');
            $bySource[$source][] = $listing;
        }

        if (count($bySource) > 1) {
            $selected = [];
            $perSource = max(4, intdiv(14, count($bySource)));

            foreach ($bySource as $sourceListings) {
                $selected = array_merge($selected, array_slice($sourceListings, 0, $perSource));
            }

            return array_slice($selected, 0, 14);
        }

        return array_slice($listings, 0, 14);
    }

    /**
     * Jonli bozor bahosi uchun e'lonlar (keshlangan yoki yangi).
     *
     * @return array<int, array<string, mixed>>
     */
    private function marketListings(string $description): array
    {
        $queries = SearchQueryNormalizer::buildSearchQueries($description);

        $parsed = $this->parse($description);
        if ($parsed !== null) {
            $hasStorage = (bool) preg_match('/\b\d+\s*(?:gb|tb)\b/iu', $description);
            $storageStr = $hasStorage ? (config("phones.storages.{$parsed['storage']}") ?? '') : '';
            $parsedQuery = trim($parsed['model'].' '.$storageStr);
            if ($parsedQuery !== '') {
                array_unshift($queries, $parsedQuery);
            }
        }

        $queries = array_values(array_unique(array_filter($queries)));
        $listings = [];

        foreach ($queries as $query) {
            $fetched = $this->fetchMarketListingsWithCache($query);
            $listings = array_merge($listings, $fetched);

            if (count($listings) >= self::MIN_COMPARABLES) {
                break;
            }
        }

        return $listings;
    }

    /**
     * Berilgan so'rov bo'yicha e'lonlarni kesh bilan olish.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchMarketListingsWithCache(string $query): array
    {
        if ($query === '') {
            return [];
        }

        if (! app()->runningUnitTests()) {
            $cacheKey = 'market-listings:'.md5(mb_strtolower($query));

            return Cache::remember($cacheKey, now()->addMinutes(15), fn (): array => $this->fetchMarketListings($query));
        }

        return $this->fetchMarketListings($query);
    }

    /**
     * OLX va BirBir'dagi e'lonlarni parallel ravishda olish.
     *
     * CloudFront PHP so'rovlarini bloklaydi, shuning uchun ikkala sayt ham
     * Jina Reader orqali o'qiladi (brauzer kabi ishlaydi).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchMarketListings(string $query): array
    {
        $olxUrl = $this->olxApiUrl($query);
        $birbirUrl = $this->birbirSearchUrl($query);

        $olx = null;
        $birbir = null;

        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('olx')->connectTimeout(5)->timeout(20)->withHeaders($this->jinaHeaders())->get($olxUrl),
                $pool->as('birbir')->connectTimeout(5)->timeout(20)->withHeaders($this->jinaHeaders())->get($birbirUrl),
            ]);

            $rawOlx = $responses['olx'] ?? null;
            $rawBirbir = $responses['birbir'] ?? null;

            $olx = $rawOlx instanceof Response && $rawOlx->successful() ? $rawOlx : null;
            $birbir = $rawBirbir instanceof Response && $rawBirbir->successful() ? $rawBirbir : null;
        } catch (Throwable $exception) {
            logger()->warning("Bozor saytlari parallel o'qilmadi, ketma-ket urinamiz.", [
                'message' => $exception->getMessage(),
            ]);
        }

        return array_merge(
            $this->parseOlxListings($olx ?? $this->fetchJina($olxUrl)),
            $this->parseBirbirListings($birbir ?? $this->fetchJina($birbirUrl)),
        );
    }

    /**
     * Jina Reader uchun so'rov sarlavhalari (kalit ixtiyoriy).
     *
     * @return array<string, string>
     */
    private function jinaHeaders(): array
    {
        $key = (string) config('services.jina.api_key');

        return array_filter([
            'Accept' => 'text/plain',
            'Authorization' => $key !== '' ? 'Bearer '.$key : null,
        ]);
    }

    /**
     * Jina Reader orqali sahifani o'qish (muvaffaqiyatsiz bo'lsa — null).
     */
    private function fetchJina(string $url): ?Response
    {
        try {
            $response = Http::connectTimeout(5)->timeout(20)->withHeaders($this->jinaHeaders())->get($url);

            return $response->successful() ? $response : null;
        } catch (Throwable $exception) {
            logger()->warning("Jina so'rovi bajarilmadi.", ['url' => $url, 'message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * OLX qidiruv API manzili (Jina orqali o'qiladi).
     */
    private function olxApiUrl(string $query): string
    {
        return 'https://r.jina.ai/https://www.olx.uz/api/v1/offers/?'.http_build_query([
            'offset' => 0,
            'limit' => 40,
            'query' => $query,
            'currency' => 'UZS',
        ]);
    }

    /**
     * BirBir qidiruv sahifasi manzili (Jina orqali o'qiladi).
     */
    private function birbirSearchUrl(string $query): string
    {
        return 'https://r.jina.ai/https://birbir.uz/uz/all/search?'.http_build_query(['search' => $query]);
    }

    /**
     * OLX javobini e'lonlar ro'yxatiga aylantirish.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseOlxListings(?Response $response): array
    {
        if ($response === null) {
            return [];
        }

        $body = $response->body();
        $start = strpos($body, '{');

        if ($start === false) {
            return [];
        }

        $offers = json_decode(substr($body, $start), true)['data'] ?? [];
        $listings = [];

        foreach ($offers as $offer) {
            if (! is_array($offer) || $this->olxIsNew($offer)) {
                continue;
            }

            $price = $this->olxPrice($offer);
            $url = (string) ($offer['url'] ?? '');

            if ($price === null || $url === '') {
                continue;
            }

            $listings[] = [
                'source' => 'olx',
                'title' => (string) ($offer['title'] ?? ''),
                'url' => $url,
                'content' => Str::limit(trim(strip_tags((string) ($offer['description'] ?? ''))), 300, ''),
                'price_uzs' => $price,
            ];
        }

        return $listings;
    }

    /**
     * BirBir sahifasidagi e'lonlarni ajratib olish.
     *
     * Markdown tuzilishi: narx satri, "## sarlavha", hudud/sana va e'lon havolasi.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseBirbirListings(?Response $response): array
    {
        if ($response === null) {
            return [];
        }

        // Yaroqsiz baytlar bo'lsa tozalaymiz — aks holda /u regex ishlamaydi.
        $markdown = mb_convert_encoding($response->body(), 'UTF-8', 'UTF-8');

        if (! preg_match_all(
            '/\[([^\]]*)\]\((https:\/\/birbir\.uz\/[^)\s]*\/o\/[^)\s]+)\)/u',
            $markdown,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        )) {
            return [];
        }

        $listings = [];

        foreach ($matches as $match) {
            $url = (string) $match[2][0];
            $offset = (int) $match[2][1];

            // Narx va sarlavha havoladan oldin keladi.
            $before = substr($markdown, max(0, $offset - 240), min(240, $offset));

            if (! preg_match_all('/(\d[\d\s]*)\s*(so\'m|сум|y\.e\.|у\.е\.)/iu', $before, $prices, PREG_SET_ORDER)) {
                continue;
            }

            // Havolaga eng yaqin narx — shu e'lonning narxi.
            $priceMatch = end($prices);
            $currencyLabel = mb_strtolower((string) $priceMatch[2]);
            $currency = str_contains($currencyLabel, "so'm") || str_contains($currencyLabel, 'сум') ? 'UZS' : 'USD';

            $price = $this->normalizePrice((int) preg_replace('/\s+/', '', (string) $priceMatch[1]), $currency);

            if ($price === null) {
                continue;
            }

            $title = trim((string) $match[1][0]);

            if (preg_match_all('/^##\s+(.+)$/mu', $before, $titles)) {
                $title = trim((string) end($titles[1]));
            }

            $listings[] = [
                'source' => 'birbir',
                'title' => $title,
                'url' => $url,
                'content' => '',
                'price_uzs' => $price,
            ];
        }

        return array_slice($listings, 0, 15);
    }

    /**
     * Bozor saytlari uchun qidiruv so'rovi (model + xotira).
     */
    private function buildSearchQuery(string $description): string
    {
        $parsed = $this->parse($description);

        if ($parsed !== null) {
            $hasStorage = (bool) preg_match('/\b\d+\s*(?:gb|tb)\b/iu', $description);
            $storageStr = $hasStorage ? (config("phones.storages.{$parsed['storage']}") ?? '') : '';

            return trim($parsed['model'].' '.$storageStr);
        }

        $queries = SearchQueryNormalizer::buildSearchQueries($description);

        return $queries[0] ?? Str::limit(trim($description), 100, '');
    }

    /**
     * OLX e'lonidagi narxni so'mda aniqlash.
     *
     * @param  array<string, mixed>  $offer
     */
    private function olxPrice(array $offer): ?int
    {
        foreach ($offer['params'] ?? [] as $param) {
            if (($param['key'] ?? null) !== 'price') {
                continue;
            }

            $value = $param['value'] ?? [];
            $converted = $value['converted_value'] ?? null;

            if (is_numeric($converted) && (int) $converted > 0) {
                return (int) $converted;
            }

            return $this->normalizePrice($value['value'] ?? null, (string) ($value['currency'] ?? 'UZS'));
        }

        return null;
    }

    /**
     * E'lon yangi telefon ekanligini aniqlash.
     *
     * @param  array<string, mixed>  $offer
     */
    private function olxIsNew(array $offer): bool
    {
        foreach ($offer['params'] ?? [] as $param) {
            if (($param['key'] ?? null) === 'state') {
                return ($param['value']['key'] ?? null) === 'new';
            }
        }

        return false;
    }

    /**
     * Tavily orqali O'zbekiston bozoridagi e'lonlarni qidirish.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function searchListings(string $description): ?array
    {
        $apiKey = (string) config('services.tavily.api_key');

        if ($apiKey === '' || ! $this->searchBudgetAvailable()) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.tavily.com/search', [
                    'query' => $this->buildQuery($description),
                    'search_depth' => 'basic',
                    'max_results' => 5,
                    'country' => 'uzbekistan',
                    'include_domains' => config('services.tavily.domains'),
                    'include_domains_mode' => 'prefer',
                ]);

            if (! $response->successful()) {
                logger()->warning('Tavily qidiruvi muvaffaqiyatsiz.', ['status' => $response->status()]);

                return null;
            }

            $this->countSearch();

            $results = $response->json('results');

            if (! is_array($results)) {
                return [];
            }

            // Faqat ishlatilgan bozor saytlarini qoldiramiz — yangi telefon do'konlari kerak emas.
            $filtered = $this->filterByDomain($results);

            if ($filtered === []) {
                logger()->info('Tavily natijalarida bozor saytlari topilmadi.', [
                    'total' => count($results),
                    'urls' => array_slice(array_column($results, 'url'), 0, 6),
                ]);
            }

            return $filtered;
        } catch (Throwable $exception) {
            logger()->warning('Tavily qidiruvida xato.', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Faqat ishlatilgan bozor domenlaridagi natijalarni qoldirish va manbasini belgilash.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<int, array<string, mixed>>
     */
    private function filterByDomain(array $listings): array
    {
        $domains = config('services.tavily.domains');
        $filtered = [];

        foreach ($listings as $listing) {
            if (! is_array($listing)) {
                continue;
            }

            $url = (string) ($listing['url'] ?? '');
            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                continue;
            }

            foreach ($domains as $domain) {
                if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                    $listing['source'] = $this->sourceFromUrl($url);
                    $filtered[] = $listing;

                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * Havola qaysi bozor saytiga tegishli ekanligini aniqlash.
     */
    private function sourceFromUrl(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return match (true) {
            $host === 'olx.uz' || str_ends_with($host, '.olx.uz') => 'olx',
            $host === 'birbir.uz' || str_ends_with($host, '.birbir.uz') => 'birbir',
            default => 'web',
        };
    }

    /**
     * Qidiruv so'rovini tuzish.
     */
    private function buildQuery(string $description): string
    {
        $parsed = $this->parse($description);

        if ($parsed !== null && ! preg_match('/(?:pakalenya|avlod|pokoleniya|gen|core\s*i|ryzen)/iu', $description)) {
            $hasStorage = (bool) preg_match('/\b\d+\s*(?:gb|tb)\b/iu', $description);
            $storageStr = $hasStorage ? (config("phones.storages.{$parsed['storage']}") ?? '') : '';

            return trim($parsed['brand'].' '.$parsed['model'].' '.$storageStr.' sotiladi');
        }

        return SearchQueryNormalizer::buildTavilyQuery($description);
    }

    /**
     * Oylik bepul kvotani tejash uchun qidiruv chegarasi.
     */
    private function searchBudgetAvailable(): bool
    {
        $limit = (int) config('services.tavily.monthly_limit');

        if ($limit <= 0) {
            return true;
        }

        return (int) Cache::get($this->searchCounterKey(), 0) < $limit;
    }

    /**
     * Bajarilgan qidiruvlar sonini oshirish.
     */
    private function countSearch(): void
    {
        $key = $this->searchCounterKey();

        Cache::put($key, (int) Cache::get($key, 0) + 1, now()->addMonths(2));
    }

    /**
     * Joriy oy uchun qidiruv hisoblagichi kaliti.
     */
    private function searchCounterKey(): string
    {
        return 'tavily-searches-'.now()->format('Y-m');
    }

    /**
     * AI orqali e'lonlarni tahlil qilish va narx oralig'ini yasash.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<string, mixed>|null
     */
    private function analyzeListings(string $description, array $listings): ?array
    {
        $accountId = (string) config('services.cloudflare.account_id');
        $apiToken = (string) config('services.cloudflare.api_token');
        $model = (string) config('services.cloudflare.model');

        if ($accountId !== '' && $apiToken !== '') {
            $prompt = "Telefon tavsifi:\n".$description."\n\nTopilgan e'lonlar:\n".$this->listingsPrompt($listings);

            $modelsToTry = array_values(array_unique(array_filter([
                $model,
                '@cf/meta/llama-3.3-70b-instruct-fp8-fast',
                '@cf/meta/llama-4-scout-17b-16e-instruct',
            ])));

            foreach ($modelsToTry as $targetModel) {
                try {
                    $response = Http::connectTimeout(5)
                        ->timeout(25)
                        ->withToken($apiToken)
                        ->acceptJson()
                        ->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$targetModel}", [
                            'messages' => [
                                ['role' => 'system', 'content' => self::ANALYST_PROMPT],
                                ['role' => 'user', 'content' => $prompt],
                            ],
                            'temperature' => 0.2,
                            'max_tokens' => 1200,
                        ]);

                    if (! $response->successful() || $response->json('success') === false) {
                        logger()->warning("AI tahlili muvaffaqiyatsiz ({$targetModel}).", ['status' => $response->status()]);

                        continue;
                    }

                    $content = (string) ($response->json('result.choices.0.message.content')
                        ?? $response->json('result.response')
                        ?? '');

                    $payload = $this->decodePayload($content);

                    if ($payload === null) {
                        logger()->info("AI javobidan JSON ajratib bo'lmadi ({$targetModel}).", [
                            'finish_reason' => $response->json('result.choices.0.finish_reason'),
                            'content' => mb_substr($content, 0, 500),
                        ]);

                        continue;
                    }

                    $valuation = $this->buildMarketValuation($description, $payload, $listings);

                    if ($valuation !== null) {
                        return $valuation;
                    }
                } catch (Throwable $exception) {
                    logger()->warning("AI tahlilida xato ({$targetModel}).", ['message' => $exception->getMessage()]);
                }
            }
        }

        // Cloudflare ishlamasa yoki sozlanmagan bo'lsa — Google Gemini API zaxirasiga o'tamiz.
        return $this->analyzeListingsWithGemini($description, $listings);
    }

    /**
     * Google Gemini API orqali e'lonlarni tahlil qilish (Cloudflare zaxirasi).
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<string, mixed>|null
     */
    private function analyzeListingsWithGemini(string $description, array $listings): ?array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-3.8-flash');

        if ($apiKey === '') {
            return null;
        }

        $prompt = "Telefon tavsifi:\n".$description."\n\nTopilgan e'lonlar:\n".$this->listingsPrompt($listings);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(25)
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => self::ANALYST_PROMPT],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1500,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if (! $response->successful()) {
                logger()->warning("Gemini AI tahlili muvaffaqiyatsiz ({$model}).", [
                    'status' => $response->status(),
                    'error' => $response->json('error.message'),
                ]);

                return null;
            }

            $content = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');

            $payload = $this->decodePayload($content);

            if ($payload === null) {
                logger()->info("Gemini AI javobidan JSON ajratib bo'lmadi ({$model}).", [
                    'content' => mb_substr($content, 0, 500),
                ]);

                return null;
            }

            return $this->buildMarketValuation($description, $payload, $listings);
        } catch (Throwable $exception) {
            logger()->warning("Gemini AI tahlilida xato ({$model}).", ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * E'lonlarni model uchun ixcham matnga aylantirish.
     *
     * @param  array<int, array<string, mixed>>  $listings
     */
    private function listingsPrompt(array $listings): string
    {
        $lines = [];

        foreach ($listings as $index => $listing) {
            $price = isset($listing['price_uzs'])
                ? number_format((int) $listing['price_uzs'], 0, ',', ' ')." so'm"
                : "noma'lum";

            $label = Valuation::sourceMeta((string) ($listing['url'] ?? ''))['label'];

            $lines[] = ($index + 1).'. '.($listing['title'] ?? '').
                "\nSayt: ".$label.
                "\nURL: ".($listing['url'] ?? '').
                "\nNarx: ".$price.
                "\nMatn: ".Str::limit((string) ($listing['content'] ?? ''), 250, '');
        }

        return implode("\n\n", $lines);
    }

    /**
     * Model javobidan JSON ajratib olish.
     *
     * @return array<string, mixed>|null
     */
    private function decodePayload(string $raw): ?array
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Model tahlilidan tekshirilgan baholash natijasini yasash.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<string, mixed>|null
     */
    private function buildMarketValuation(string $description, array $payload, array $listings): ?array
    {
        // Qo'llab-quvvatlanmaydigan narsa (maishiy texnika, hayvon va h.k.) —
        // e'lonlar bo'sh bo'lishi ham mumkin, shuning uchun erta qaytaramiz.
        if (($payload['device_type'] ?? '') === 'other') {
            return ['device_type' => 'other'];
        }

        $allowedUrls = array_column($listings, 'url');

        // O'zimiz olgan narx va manbalar — model ularni o'zgartirmasligi uchun.
        $priceByUrl = [];
        $sourceByUrl = [];

        foreach ($listings as $listing) {
            $url = (string) ($listing['url'] ?? '');

            if ($url === '') {
                continue;
            }

            if (isset($listing['price_uzs'])) {
                $priceByUrl[$url] = (int) $listing['price_uzs'];
            }

            $sourceByUrl[$url] = (string) ($listing['source'] ?? $this->sourceFromUrl($url));
        }

        $comparables = [];

        foreach ($payload['comparables'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = (string) ($item['url'] ?? '');

            // Model o'ylab topgan manbalarni tashlab yuboramiz.
            if ($url === '' || ! in_array($url, $allowedUrls, true)) {
                continue;
            }

            $price = $priceByUrl[$url]
                ?? $this->normalizePrice($item['price'] ?? null, (string) ($item['currency'] ?? 'UZS'));

            if ($price === null) {
                continue;
            }

            $comparables[] = [
                'title' => Str::limit((string) ($item['title'] ?? $url), 120, ''),
                'url' => $url,
                'price_uzs' => $price,
                'source' => $sourceByUrl[$url] ?? $this->sourceFromUrl($url),
            ];
        }

        if (count($comparables) < self::MIN_COMPARABLES) {
            logger()->info("Bozor tahlilida yetarli e'lon topilmadi.", [
                'found' => count($payload['comparables'] ?? []),
                'accepted' => count($comparables),
                'listing_urls' => array_slice($allowedUrls, 0, 6),
                'comparables' => $payload['comparables'] ?? [],
            ]);

            return null;
        }

        // Median atrofidagi narxlarnigina qoldiramiz — boshqa model yoki noto'g'ri e'lonlarni tashlaymiz.
        $median = $this->median(array_column($comparables, 'price_uzs'));

        $comparables = array_values(array_filter(
            $comparables,
            fn (array $item): bool => $item['price_uzs'] >= $median * 0.5 && $item['price_uzs'] <= $median * 1.6,
        ));

        if (count($comparables) < self::MIN_COMPARABLES) {
            logger()->info("Bozor e'lonlari bir-biriga mos kelmadi.", [
                'median' => $median,
                'accepted' => count($comparables),
            ]);

            return null;
        }

        $prices = array_column($comparables, 'price_uzs');
        sort($prices);

        $low = (int) reset($prices);
        $high = (int) end($prices);
        $rate = max(1, (int) config('phones.usd_to_uzs'));

        $parsed = $this->parse($description);

        return [
            'device_type' => (string) ($payload['device_type'] ?? 'phone'),
            'brand' => (string) ($payload['brand'] ?? $parsed['brand'] ?? 'Qurilma'),
            'model' => (string) ($payload['model'] ?? $parsed['model'] ?? Str::limit($description, 60, '')),
            'storage' => $this->sanitizeStorage($payload['storage_gb'] ?? $parsed['storage'] ?? 128),
            'battery' => $this->sanitizeBattery($payload['battery'] ?? $parsed['battery'] ?? 90),
            'condition' => $this->sanitizeCondition($payload['condition'] ?? $parsed['condition'] ?? 'good'),
            'price_low' => (int) round($low / $rate),
            'price_high' => (int) round($high / $rate),
            'price_low_uzs' => $low,
            'price_high_uzs' => $high,
            'price_source' => 'market',
            'sources' => array_slice($comparables, 0, 5),
            'checked_at' => now(),
            'confidence' => min(88, 55 + count($comparables) * 4),
            'insight' => $this->marketInsight($payload, $comparables),
            'photos_count' => 0,
            'recommendation' => $this->sanitizeRecommendation($payload['recommendation'] ?? null, $payload['model'] ?? $parsed['model'] ?? ''),
        ];
    }

    /**
     * Model izohini topilgan manbalar haqidagi aniq ma'lumot bilan to'ldirish.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $comparables
     */
    private function marketInsight(array $payload, array $comparables): string
    {
        $insight = trim((string) ($payload['insight'] ?? ''));

        // Model ba'zan o'zi narx yozadi — olib tashlaymiz, chunki interfeysda
        // aniq oraliq ko'rsatiladi va takrorlanish bo'lmasin.
        $insight = trim((string) preg_replace('/[^.!?]*\d[^.!?]*so\'m[^.!?]*[.!?]?/iu', ' ', $insight));
        $insight = trim((string) preg_replace('/\s{2,}/u', ' ', $insight));

        $labels = [];

        foreach (array_unique(array_column($comparables, 'source')) as $source) {
            $labels[] = match ($source) {
                'olx' => 'OLX',
                'birbir' => 'BirBir',
                default => 'boshqa bozor saytlari',
            };
        }

        $prices = array_column($comparables, 'price_uzs');
        sort($prices);

        $facts = sprintf(
            "Tahlil %s manbalaridagi %d ta mos e'lon asosida o'tkazildi — narxlar %s – %s so'm oralig'ida.",
            implode(' va ', $labels),
            count($comparables),
            number_format((int) reset($prices), 0, ',', ' '),
            number_format((int) end($prices), 0, ',', ' '),
        );

        return $insight === '' ? $facts : $insight.' '.$facts;
    }

    /**
     * Bozor maslahatini tozalash va standart formatga keltirish.
     *
     * @return array{action: string, badge: string, reason: string, disclaimer: string}
     */
    private function sanitizeRecommendation(mixed $data, string $modelName = ''): array
    {
        $defaultDisclaimer = "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.";

        if (! is_array($data)) {
            return $this->defaultRecommendation($modelName, 90);
        }

        $action = match ($data['action'] ?? '') {
            'sell_now' => 'sell_now',
            'wait' => 'wait',
            default => 'fair_price',
        };

        $badge = trim((string) ($data['badge'] ?? ''));
        if ($badge === '') {
            $badge = match ($action) {
                'sell_now' => 'Hozir sotish tavsiya etiladi',
                'wait' => 'Biroz kutish maqbul',
                'fair_price' => 'Bozor narxida sotish mumkin',
            };
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            $reason = "Hozirgi bozor narxlari barqaror shakllangan bo'lib, o'rtacha qiymatda sotuvga qo'yish mumkin.";
        }

        return [
            'action' => $action,
            'badge' => $badge,
            'reason' => $reason,
            'disclaimer' => $defaultDisclaimer,
        ];
    }

    /**
     * Standart bozor tavsiyasi (zaxira).
     *
     * @return array{action: string, badge: string, reason: string, disclaimer: string}
     */
    private function defaultRecommendation(string $modelName, int $battery): array
    {
        $action = $battery < 82 ? 'sell_now' : 'fair_price';
        $badge = $action === 'sell_now' ? 'Hozir sotish tavsiya etiladi' : 'Bozor narxida sotish mumkin';

        $reason = $action === 'sell_now'
            ? 'Batareya quvvati kamayishi bilan qurilma qiymati tezroq pasayadi. Qulay narxda tezroq sotish tavsiya etiladi.'
            : "Qurilma bozorida talab barqaror. Uni o'rtacha bozor narxi atrofida sotuvga qo'yish maqsadga muvofiq.";

        return [
            'action' => $action,
            'badge' => $badge,
            'reason' => $reason,
            'disclaimer' => "Ixtiyor o'zingizda, bu sun'iy intellektning bozor tahlili bo'yicha maslahati xolos.",
        ];
    }

    /**
     * Narxlar medianasini hisoblash.
     *
     * @param  array<int, int>  $values
     */
    private function median(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? (float) $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Narxni so'mga keltirish (dollar bo'lsa — kurs bo'yicha).
     */
    private function normalizePrice(mixed $price, string $currency): ?int
    {
        if (! is_numeric($price)) {
            return null;
        }

        $value = (float) $price;

        if ($value <= 0) {
            return null;
        }

        $rate = max(1, (int) config('phones.usd_to_uzs'));

        // "UYE" — OLX'dagi shartli birlik (у.е.), dollarga teng.
        if (in_array(strtoupper($currency), ['USD', 'UYE'], true)) {
            $value *= $rate;
        }

        return (int) round($value);
    }

    /**
     * Xotira hajmini ruxsat etilgan qiymatlarga keltirish.
     */
    private function sanitizeStorage(mixed $storage): int
    {
        return $this->parser->sanitizeStorage($storage);
    }

    /**
     * Batareya foizini 0–100 oralig'iga keltirish.
     */
    private function sanitizeBattery(mixed $battery): int
    {
        return $this->parser->sanitizeBattery($battery);
    }

    /**
     * Holat qiymatini ruxsat etilganlarga keltirish.
     */
    private function sanitizeCondition(mixed $condition): string
    {
        return $this->parser->sanitizeCondition($condition);
    }

    /**
     * Matndan model, xotira, batareya va holatni ajratib olish.
     *
     * @return array<string, mixed>|null
     */
    private function parse(string $description): ?array
    {
        return $this->parser->parse($description);
    }

    /**
     * Qo'llab-quvvatlanmaydigan narsa (maishiy texnika, hayvon va h.k.) uchun javob.
     */
    private function unsupportedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'unsupported',
            'message' => "Bu turdagi narsa baholanmaydi. Narxla telefon, planshet va noutbuklarni baholaydi — qurilma nomini aniqroq yozib ko'ring.",
        ]);
    }

    /**
     * Model aniqlanmagan qurilma uchun so'rovni saqlash.
     */
    private function storeRequest(Request $request, string $description): JsonResponse
    {
        $valuationRequest = new ValuationRequest(['description' => $description]);
        $valuationRequest->user_id = $request->user()?->id;
        $valuationRequest->save();

        $this->notifyAdmin($valuationRequest);

        return response()->json([
            'status' => 'requested',
            'message' => "Bu model bo'yicha yetarli e'lon topilmadi. So'rovingiz qabul qilindi — mutaxassis narxni aniqlab, siz bilan bog'lanadi.",
        ]);
    }

    /**
     * So'rov haqida adminga Telegram orqali xabar yuborish (sozlangan bo'lsa).
     */
    private function notifyAdmin(ValuationRequest $valuationRequest): void
    {
        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.admin_chat_id');

        if ($token === '' || $chatId === '') {
            return;
        }

        $lines = ["📱 Yangi baholash so'rovi"];
        $lines[] = 'Foydalanuvchi: '.($valuationRequest->user?->name ?: 'mehmon');
        $lines[] = '';
        $lines[] = $valuationRequest->description;

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => implode("\n", $lines),
            ]);
        } catch (Throwable $exception) {
            logger()->warning("Telegram so'rov xabari yuborilmadi.", [
                'request_id' => $valuationRequest->id,
            ]);
        }
    }

    /**
     * Katalogdagi taxminiy narxni hisoblash.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function calculate(array $input): array
    {
        $catalog = config('phones');
        $basePrice = (int) $this->modelPrice($input['brand'], $input['model']);
        $battery = (int) $input['battery'];

        $storageBoost = (float) ($catalog['storage_boost'][$input['storage']] ?? 0.0);
        $conditionDelta = (float) ($catalog['condition_delta'][$input['condition']] ?? 0.0);
        $batteryPenalty = $battery < 85 ? -((85 - $battery) * 0.005) : 0.0;

        $midpoint = $basePrice * (1 + $storageBoost + $conditionDelta + $batteryPenalty);
        $low = max(20, (int) round($midpoint * 0.955 / 5) * 5);
        $high = max($low + 10, (int) round($midpoint * 1.045 / 5) * 5);

        return [
            'brand' => $input['brand'],
            'model' => $input['model'],
            'storage' => (int) $input['storage'],
            'battery' => $battery,
            'condition' => $input['condition'],
            'price_low' => $low,
            'price_high' => $high,
            'price_source' => 'catalog',
            'sources' => null,
            'checked_at' => now(),
            'confidence' => 60,
            'insight' => $this->insight($input, $battery),
            'photos_count' => 0,
            'recommendation' => $this->defaultRecommendation($input['model'] ?? '', $battery),
        ];
    }

    /**
     * Tanlangan modelning bazaviy narxi (dollar).
     */
    private function modelPrice(string $brand, string $model): ?int
    {
        $match = collect(config("phones.brands.{$brand}"))->firstWhere('name', $model);

        return $match ? (int) $match['price'] : null;
    }

    /**
     * Holat va batareya asosidagi xulosa.
     *
     * @param  array<string, mixed>  $input
     */
    private function insight(array $input, int $battery): string
    {
        $insights = [
            match ($input['condition']) {
                'ideal' => 'Qurilma ideal holatda — bu narxni yuqori chegaraga yaqinlashtiradi.',
                'scratched' => "Ko'rinadigan tirnalishlar narxni biroz pasaytiradi, ammo talab saqlanib qoladi.",
                default => "Yaxshi holat — bozorda eng ko'p uchraydigan va talabgir kategoriya.",
            },
            match (true) {
                $battery >= 90 => 'Batareya quvvati '.$battery.'% — xaridorlar uchun kuchli afzallik.',
                $battery >= 80 => 'Batareya quvvati '.$battery."% — o'rtacha ko'rsatkich.",
                default => 'Batareya quvvati '.$battery.'% — xaridorlar almashtirish xarajatini hisobga olishi mumkin.',
            },
        ];

        if ((int) $input['storage'] >= 256) {
            $insights[] = "Katta xotira hajmi qurilma qiymatiga ijobiy ta'sir qiladi.";
        }

        return implode(' ', $insights);
    }

    /**
     * Natijani foydalanuvchiga ko'rsatiladigan ko'rinishga keltirish.
     *
     * @param  array<string, mixed>  $valuation
     * @return array<string, mixed>
     */
    private function present(array $valuation): array
    {
        $storages = config('phones.storages');
        $conditions = config('phones.conditions');
        $rate = max(1, (int) config('phones.usd_to_uzs'));

        $lowUzs = $valuation['price_low_uzs'] ?? null;
        $highUzs = $valuation['price_high_uzs'] ?? null;
        $hasUzs = $lowUzs !== null && $highUzs !== null;

        $sources = [];

        foreach ($valuation['sources'] ?? [] as $source) {
            $url = (string) ($source['url'] ?? '');
            $meta = Valuation::sourceMeta($url);

            $sources[] = [
                'title' => $source['title'] ?? ($url !== '' ? $url : ''),
                'url' => $url,
                'price' => isset($source['price_uzs'])
                    ? number_format((int) $source['price_uzs'], 0, ',', ' ')." so'm"
                    : null,
                'source' => $source['source'] ?? $meta['source'],
                'label' => $meta['label'],
                'short' => $meta['short'],
                'favicon' => $meta['favicon'],
            ];
        }

        return [
            'range' => $hasUzs
                ? number_format($lowUzs, 0, ',', ' ').' – '.number_format($highUzs, 0, ',', ' ')." so'm"
                : Valuation::som($valuation['price_low']).' – '.Valuation::som($valuation['price_high'])." so'm",
            'range_usd' => $hasUzs
                ? '$'.intdiv($lowUzs, $rate).' – $'.intdiv($highUzs, $rate)
                : '$'.$valuation['price_low'].' – $'.$valuation['price_high'],
            'device' => $valuation['brand'].' '.$valuation['model'].' · '.($storages[$valuation['storage']] ?? ''),
            'condition' => $conditions[$valuation['condition']]['label'] ?? 'Yaxshi',
            'battery' => $valuation['battery'].'%',
            'storage' => $storages[$valuation['storage']] ?? '',
            'confidence' => $valuation['confidence'],
            'insight' => $valuation['insight'],
            'price_source' => $valuation['price_source'] ?? 'catalog',
            'source_label' => ($valuation['price_source'] ?? 'catalog') === 'market'
                ? 'Jonli bozor tahlili'
                : 'Katalog (taxminiy)',
            'sources' => $sources,
            'checked_at' => isset($valuation['checked_at'])
                ? $valuation['checked_at']->format('d.m.Y H:i')
                : null,
            'recommendation' => $valuation['recommendation'] ?? $this->defaultRecommendation(
                $valuation['model'] ?? '',
                (int) ($valuation['battery'] ?? 90)
            ),
        ];
    }
}
