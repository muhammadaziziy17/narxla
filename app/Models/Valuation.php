<?php

namespace App\Models;

use Database\Factories\ValuationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'brand',
    'model',
    'storage',
    'battery',
    'condition',
    'price_low',
    'price_high',
    'price_low_uzs',
    'price_high_uzs',
    'price_source',
    'sources',
    'checked_at',
    'confidence',
    'insight',
    'photos_count',
    'recommendation',
])]
class Valuation extends Model
{
    /** @use HasFactory<ValuationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dollarni so'mga o'girib, bo'sh joy bilan formatlaydi.
     */
    public static function som(int $usd): string
    {
        return number_format($usd * (int) config('phones.usd_to_uzs'), 0, ',', ' ');
    }

    /**
     * So'mda ifodalangan narx oralig'i — jonli tahlilda tayyor so'm qiymati ishlatiladi.
     */
    public function priceRangeSom(): string
    {
        if ($this->price_low_uzs !== null && $this->price_high_uzs !== null) {
            return $this->formatSom($this->price_low_uzs).' – '.$this->formatSom($this->price_high_uzs)." so'm";
        }

        return static::som($this->price_low).' – '.static::som($this->price_high)." so'm";
    }

    /**
     * Dollarda ifodalangan narx oralig'i.
     */
    public function priceRangeUsd(): string
    {
        $rate = max(1, (int) config('phones.usd_to_uzs'));

        if ($this->price_low_uzs !== null && $this->price_high_uzs !== null) {
            return '$'.intdiv($this->price_low_uzs, $rate).' – $'.intdiv($this->price_high_uzs, $rate);
        }

        return '$'.$this->price_low.' – $'.$this->price_high;
    }

    /**
     * Narx manbasi uchun yorliq.
     */
    public function sourceLabel(): string
    {
        return $this->price_source === 'market' ? 'Jonli bozor tahlili' : 'Katalog (taxminiy)';
    }

    /**
     * E'lon havolasidan manba ma'lumotlari: nomi, qisqa harflar va favicon.
     *
     * @return array{source: string, label: string, short: string, favicon: string|null}
     */
    public static function sourceMeta(string $url): array
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $host = Str::after($host, 'www.');

        $source = match (true) {
            $host === 'olx.uz' || str_ends_with($host, '.olx.uz') => 'olx',
            $host === 'birbir.uz' || str_ends_with($host, '.birbir.uz') => 'birbir',
            default => 'web',
        };

        return [
            'source' => $source,
            'label' => match ($source) {
                'olx' => 'OLX',
                'birbir' => 'BirBir',
                default => $host,
            },
            'short' => match ($source) {
                'olx' => 'OLX',
                'birbir' => 'BB',
                default => mb_strtoupper(mb_substr($host, 0, 2)),
            },
            'favicon' => $host !== ''
                ? 'https://www.google.com/s2/favicons?sz=64&domain='.$host
                : null,
        ];
    }

    /**
     * Sonni bo'sh joy bilan formatlash.
     */
    private function formatSom(int $value): string
    {
        return number_format($value, 0, ',', ' ');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'storage' => 'integer',
            'battery' => 'integer',
            'price_low' => 'integer',
            'price_high' => 'integer',
            'price_low_uzs' => 'integer',
            'price_high_uzs' => 'integer',
            'sources' => 'array',
            'checked_at' => 'datetime',
            'confidence' => 'integer',
            'photos_count' => 'integer',
            'recommendation' => 'array',
        ];
    }
}
