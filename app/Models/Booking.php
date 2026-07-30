<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    protected $fillable = [
        'parent_id',
        'nanny_id',
        'start_time',
        'end_time',
        'status', // pending, confirmed, rejected, cancelled
        'total_price',
        'address_string',
        'location',
        'latitude',
        'longitude',
        'cancellation_comment',
    ];

    protected $appends = [
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function nanny(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nanny_id');
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(\App\Models\Review::class);
    }

    public function escrow(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Escrow::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
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
