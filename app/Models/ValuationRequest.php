<?php

namespace App\Models;

use Database\Factories\ValuationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand',
    'description',
    'battery',
    'condition',
    'photos_count',
    'status',
])]
class ValuationRequest extends Model
{
    /** @use HasFactory<ValuationRequestFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'battery' => 'integer',
            'photos_count' => 'integer',
        ];
    }
}
