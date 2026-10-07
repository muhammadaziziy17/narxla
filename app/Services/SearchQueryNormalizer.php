<?php

namespace App\Services;

use Illuminate\Support\Str;

class SearchQueryNormalizer
{
    /**
     * Foydalanuvchi kiritgan erkin matndan bozor saytlari (OLX, BirBir) uchun
     * eng toza va yuqori natija beruvchi qidiruv so'rovlarini shakllantirish.
     *
     * @return array<int, string>
     */
    public static function buildSearchQueries(string $description): array
    {
        $desc = trim($description);
        $lower = mb_strtolower($desc);

        // 1. Protsessor va avlod (Generation / Pokoleniye / Avlod) aniqlash
        // Masalan: "core i5 13chi pakalenya", "i7 12-avlod", "13-pokoleniya i5", "ryzen 5 5600"
        $cpu = null;
        $gen = null;

        if (preg_match('/(?:core\s*)?(i[3579]|ryzen\s*[3579])[\s\-]*(?:avlod|pokoleniya|pokolenie|gen)?[\s\:\-]*(\d{1,2})[\s\-]*(?:chi|inchi)?\s*(?:pakalenya|pakaleniya|pokolenya|pokoleniye|pokoleniya|avlod|avlodli|gen|th gen|th|pok)?/iu', $lower, $m)) {
            $cpu = ucfirst(trim($m[1]));
            $gen = trim($m[2]);
        } elseif (preg_match('/(\d{1,2})[\s\-]*(?:chi|inchi)?\s*(?:pakalenya|pakaleniya|pokolenya|pokoleniye|pokoleniya|avlod|avlodli|gen|th gen|th|pok)?[\s\:\-]*(?:core\s*)?(i[3579]|ryzen\s*[3579])/iu', $lower, $m)) {
            $gen = trim($m[1]);
            $cpu = ucfirst(trim($m[2]));
        }

        $laptopModel = self::detectLaptopModel($lower);

        if ($cpu !== null && $gen !== null) {
            $queries = [];
            if ($laptopModel !== '') {
                $queries[] = "{$laptopModel} Core {$cpu} {$gen}";
                $queries[] = "{$laptopModel} {$cpu} {$gen}";
            }
            $queries[] = "Core {$cpu} {$gen}";
            $queries[] = "{$cpu} {$gen}-pokoleniya";
            $queries[] = "{$cpu} {$gen}th";

            return array_values(array_unique($queries));
        }

        // 2. Oddiy tozalash: sotuv, holat va ortiqcha shovqin so'zlarni olib tashlash
        $cleaned = self::stripStopWords($desc);

        if ($cleaned !== '') {
            $queries = [$cleaned];

            // Agar avlod so'zi qolib ketgan bo'lsa (masalan "13-avlod")
            if (preg_match('/(\d{1,2})[\s\-]*(?:chi|inchi)?\s*(?:pakalenya|pakaleniya|pokolenya|pokoleniye|pokoleniya|avlod|gen)/iu', $cleaned, $genMatch)) {
                $num = $genMatch[1];
                $simplified = trim(preg_replace('/(\d{1,2})[\s\-]*(?:chi|inchi)?\s*(?:pakalenya|pakaleniya|pokolenya|pokoleniye|pokoleniya|avlod|gen)[^\s]*/iu', $num, $cleaned));
                if ($simplified !== '' && $simplified !== $cleaned) {
                    $queries[] = $simplified;
                }
            }

            return array_values(array_unique($queries));
        }

        return [Str::limit($desc, 60, '')];
    }

    /**
     * Tavily qidiruv tizimi uchun moslashtirilgan so'rov.
     */
    public static function buildTavilyQuery(string $description): string
    {
        $queries = self::buildSearchQueries($description);
        $primary = $queries[0] ?? $description;

        $lower = mb_strtolower($description);
        $isLaptop = preg_match('/(?:core\s*i[3579]|ryzen|noutbuk|laptop|thinkpad|victus|legion|ideapad|zenbook|vivobook|macbook)/iu', $lower);

        if ($isLaptop && ! str_contains(mb_strtolower($primary), 'noutbuk')) {
            return "{$primary} noutbuk";
        }

        return "{$primary} sotiladi";
    }

    /**
     * Noutbuk brendi yoki model turini aniqlash.
     */
    private static function detectLaptopModel(string $text): string
    {
        $models = [
            'victus' => 'HP Victus',
            'omen' => 'HP Omen',
            'pavilion' => 'HP Pavilion',
            'elitebook' => 'HP EliteBook',
            'probook' => 'HP ProBook',
            'thinkpad' => 'Lenovo ThinkPad',
            'ideapad' => 'Lenovo IdeaPad',
            'legion' => 'Lenovo Legion',
            'yoga' => 'Lenovo Yoga',
            'tuf' => 'Asus TUF',
            'rog' => 'Asus ROG',
            'zenbook' => 'Asus ZenBook',
            'vivobook' => 'Asus VivoBook',
            'nitro' => 'Acer Nitro',
            'predator' => 'Acer Predator',
            'aspire' => 'Acer Aspire',
            'swift' => 'Acer Swift',
            'xps' => 'Dell XPS',
            'latitude' => 'Dell Latitude',
            'inspiron' => 'Dell Inspiron',
            'vostro' => 'Dell Vostro',
            'surface' => 'Microsoft Surface',
            'macbook pro' => 'MacBook Pro',
            'macbook air' => 'MacBook Air',
            'macbook' => 'MacBook',
            'hp' => 'HP',
            'lenovo' => 'Lenovo',
            'asus' => 'Asus',
            'acer' => 'Acer',
            'dell' => 'Dell',
        ];

        foreach ($models as $key => $name) {
            if (preg_match('/\b'.preg_quote($key, '/').'\b/iu', $text)) {
                return $name;
            }
        }

        return '';
    }

    /**
     * Foydalanuvchi matnidan sotuv, holat va ortiqcha shovqin so'zlarni tozalash.
     */
    private static function stripStopWords(string $text): string
    {
        $stopWords = [
            'sotiladi', 'сотилади', 'sotaman', 'сотаман', 'sotilmoqda', 'sotamiz', 'narxi', 'narx',
            'bormi', 'kerak', 'qidiryapman', 'qidiruv', 'db qdirsam', 'qidirsam', 'nechpul', 'qancha',
            'yangi', 'yengi', 'янги', 'новый', 'новая', 'новые', 'ideal', 'идеал', 'idealda',
            'chotki', 'zo\'r', 'zor', 'alo', 'a\'lo', 'yaxshi', 'yaxwi', 'ishlatilgan', 'ishlatilmagan',
            'б/у', 'бу', 'bu', 'karobka', 'korobka', 'коробка', 'karopka', 'koropka', 'karobkasi',
            'dokument', 'dokumenti', 'hujjat', 'hujjati', 'bor', 'yoq', 'йук', 'бор', 'ochilmagan',
            'aybi', 'aybi yoq', 'aybsiz', 'tirnalgan', 'qirilgan', 'sinmagan', 'urilgan',
            'holati', 'holatda', 'состояние', 'sostoyanie',
            'toshkent', 'toshkentda', 'tashkent', 'tashkentda', 'samarqand', 'buxoro', 'andijon',
            'telefon', 'smartfon', 'telefonim', 'noutbuk', 'laptop', 'planshet',
        ];

        $escaped = array_map(fn (string $w): string => preg_quote($w, '/'), $stopWords);
        $pattern = '/\b('.implode('|', $escaped).')\b/iu';

        $cleaned = preg_replace($pattern, ' ', $text);
        $cleaned = preg_replace('/[,\.!?]+/', ' ', (string) $cleaned);

        return trim(preg_replace('/\s+/', ' ', (string) $cleaned));
    }
}
