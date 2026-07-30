<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NannySlot extends Model
{
    protected $fillable = [
        'nanny_id',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function nanny(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nanny_id');
    }
}
