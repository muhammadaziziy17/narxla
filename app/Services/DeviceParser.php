<?php

namespace App\Services;

class DeviceParser
{
    /**
     * Matndan model, xotira, batareya va holatni ajratib olish.
     *
     * @return array{brand: string, model: string, storage: int, battery: int, condition: string, photos_count: int}|null
     */
    public function parse(string $description): ?array
    {
        $model = $this->matchModel($description);

        if ($model === null) {
            return null;
        }

        return [
            'brand' => $model['brand'],
            'model' => $model['name'],
            'storage' => $this->extractStorage($description),
            'battery' => $this->extractBattery($description),
            'condition' => $this->extractCondition($description),
            'photos_count' => 0,
        ];
    }

    /**
     * Matnda uchraydigan eng uzun model nomini topish.
     *
     * @return array{brand: string, name: string, price: int}|null
     */
    public function matchModel(string $description): ?array
    {
        $haystack = $this->normalize($description);

        if ($haystack === '') {
            return null;
        }

        $best = null;
        $bestLength = 0;

        /** @var array<string, array<int, array{name: string, price: int}>> $brands */
        $brands = config('phones.brands', []);

        foreach ($brands as $brand => $models) {
            foreach ($models as $model) {
                $needle = $this->normalize($model['name']);

                if ($needle === '' || ! str_contains($haystack, $needle)) {
                    continue;
                }

                // Eng uzun moslik yutadi: "iPhone 15 Pro Max" — "iPhone 15" dan ustun.
                if (mb_strlen($needle) > $bestLength) {
                    $best = ['brand' => $brand, 'name' => $model['name'], 'price' => (int) $model['price']];
                    $bestLength = mb_strlen($needle);
                }
            }
        }

        return $best;
    }

    /**
     * Matndan xotira hajmini aniqlash (topilmasa — 128 GB).
     */
    public function extractStorage(string $description): int
    {
        if (preg_match('/(\d+)\s*(tb|тб)/iu', $description)) {
            return 1024;
        }

        if (preg_match('/(\d+)\s*(gb|гб)/iu', $description, $matches)) {
            return $this->sanitizeStorage($matches[1]);
        }

        // "8/256" kabi yozuvlar uchun — yalang'och son.
        if (preg_match('/\b(64|128|256|512)\b/', $description, $matches)) {
            return (int) $matches[1];
        }

        return 128;
    }

    /**
     * Matndan batareya quvvatini aniqlash (topilmasa — 90%).
     */
    public function extractBattery(string $description): int
    {
        if (preg_match('/(\d{1,3})\s*%/', $description, $matches)) {
            return $this->sanitizeBattery($matches[1]);
        }

        if (preg_match('/(?:batareya|батарея|акб|akb)[^\d]{0,12}(\d{1,3})/iu', $description, $matches)) {
            return $this->sanitizeBattery($matches[1]);
        }

        return 90;
    }

    /**
     * Matndan qurilma holatini aniqlash (topilmasa — yaxshi).
     */
    public function extractCondition(string $description): string
    {
        $text = mb_strtolower($description);

        if (preg_match('/ideal|yangi|zo.?r|отличн|идеал/u', $text)) {
            return 'ideal';
        }

        if (preg_match('/tirnalgan|chizilgan|shikast|singan|разбит|царапин|скол/u', $text)) {
            return 'scratched';
        }

        return 'good';
    }

    /**
     * Xotira hajmini ruxsat etilgan qiymatlarga keltirish.
     */
    public function sanitizeStorage(mixed $storage): int
    {
        $value = (int) $storage;

        return in_array($value, [64, 128, 256, 512, 1024], true) ? $value : 128;
    }

    /**
     * Batareya foizini 0–100 oralig'iga keltirish.
     */
    public function sanitizeBattery(mixed $battery): int
    {
        $value = (int) $battery;

        return $value >= 0 && $value <= 100 ? $value : 90;
    }

    /**
     * Holat qiymatini ruxsat etilganlarga keltirish.
     */
    public function sanitizeCondition(mixed $condition): string
    {
        $value = (string) $condition;

        /** @var array<string, mixed> $conditions */
        $conditions = config('phones.conditions', []);

        return array_key_exists($value, $conditions) ? $value : 'good';
    }

    /**
     * Solishtirish uchun matnni soddalashtirish: kichik harf, faqat harf va raqam.
     */
    public function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9+а-яё]/u', '', mb_strtolower($value)) ?? '';
    }
}
