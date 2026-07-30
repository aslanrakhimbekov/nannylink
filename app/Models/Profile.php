<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'city',
        'iin',
        'avatar_url',
        'video_url',
        'bio',
        'bio_kk',
        'languages',
        'skills',
        'hourly_rate',
        'experience_years',
        'balance_coins',
        'is_verified',
        'location',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'languages' => 'array',
        'skills' => 'array',
        'is_verified' => 'boolean',
    ];

    protected $appends = [
        'latitude',
        'longitude',
        'average_rating',
        'compliments_summary',
        'is_new_nanny',
        'effective_hourly_rate',
        'promo_discount',
    ];

    public function getIsNewNannyAttribute(): bool
    {
        if (!$this->user_id) {
            return false;
        }
        $confirmedCount = \App\Models\Booking::where('nanny_id', $this->user_id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();
        return $confirmedCount < 3;
    }

    public function getEffectiveHourlyRateAttribute(): int
    {
        $original = (int) ($this->hourly_rate ?? 0);
        if ($this->getIsNewNannyAttribute()) {
            return max(0, $original - 500);
        }
        return $original;
    }

    public function getPromoDiscountAttribute(): int
    {
        return $this->getIsNewNannyAttribute() ? 500 : 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAverageRatingAttribute()
    {
        $nannyId = $this->user_id;
        $reviewsCount = \App\Models\Review::where('nanny_id', $nannyId)->count();
        $sumRatings = \App\Models\Review::where('nanny_id', $nannyId)->sum('rating');
        
        $C = 5;
        $m = 4.5;
        
        return round(($C * $m + $sumRatings) / ($C + $reviewsCount), 2);
    }

    public function getComplimentsSummaryAttribute()
    {
        $nannyId = $this->user_id;
        $reviews = \App\Models\Review::where('nanny_id', $nannyId)->whereNotNull('compliments')->get();
        $summary = [];
        foreach ($reviews as $review) {
            if (is_array($review->compliments)) {
                foreach ($review->compliments as $tag) {
                    $summary[$tag] = ($summary[$tag] ?? 0) + 1;
                }
            }
        }
        return $summary;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm)
    {
        $radiusMeters = (float) $radiusKm * 1000;
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        return $query->whereRaw("ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)", [$longitude, $latitude, $radiusMeters])
                     ->orderByRaw("ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)", [$longitude, $latitude]);
    }

    private ?float $latVal = null;
    private ?float $lngVal = null;

    public function setLatitudeAttribute($value)
    {
        $this->latVal = (float) $value;
        $this->updateLocationRaw();
    }

    public function setLongitudeAttribute($value)
    {
        $this->lngVal = (float) $value;
        $this->updateLocationRaw();
    }

    protected function updateLocationRaw()
    {
        if ($this->latVal !== null && $this->lngVal !== null) {
            $this->attributes['location'] = DB::raw("ST_SetSRID(ST_MakePoint({$this->lngVal}, {$this->latVal}), 4326)::geography");
        }
    }

    public function getLatitudeAttribute()
    {
        if ($this->latVal !== null) {
            return $this->latVal;
        }
        $coords = $this->parseWkb($this->attributes['location'] ?? null);
        return $coords ? $coords['latitude'] : null;
    }

    public function getLongitudeAttribute()
    {
        if ($this->lngVal !== null) {
            return $this->lngVal;
        }
        $coords = $this->parseWkb($this->attributes['location'] ?? null);
        return $coords ? $coords['longitude'] : null;
    }

    protected function parseWkb($hex)
    {
        if (!$hex) {
            return null;
        }
        try {
            if (is_resource($hex)) {
                return null;
            }
            $binary = @hex2bin($hex);
            if (!$binary || strlen($binary) < 21) {
                return null;
            }
            $byteOrder = unpack('C', substr($binary, 0, 1))[1];
            $isLittleEndian = ($byteOrder === 1);

            $format = $isLittleEndian ? 'Vtype' : 'Ntype';
            $type = unpack($format, substr($binary, 1, 4))['type'];

            $hasSRID = ($type & 0x20000000) != 0;
            $offset = 5;
            if ($hasSRID) {
                $offset += 4;
            }

            $doubleFormat = $isLittleEndian ? 'e' : 'E';
            $x = unpack($doubleFormat, substr($binary, $offset, 8))[1];
            $y = unpack($doubleFormat, substr($binary, $offset + 8, 8))[1];

            return [
                'longitude' => (float) $x,
                'latitude' => (float) $y,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
