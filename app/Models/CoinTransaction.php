<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\CoinTransactionType;

class CoinTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'user_id',
        'booking_id',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => CoinTransactionType::class,
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
