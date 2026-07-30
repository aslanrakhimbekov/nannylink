<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'booking_id',
        'parent_id',
        'nanny_id',
        'rating',
        'comment',
        'compliments',
    ];

    protected $casts = [
        'rating' => 'integer',
        'compliments' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function nanny(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nanny_id');
    }
}
