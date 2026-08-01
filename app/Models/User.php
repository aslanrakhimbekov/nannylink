<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserLanguage;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    public function getFilamentName(): string
    {
        return $this->phone ?? $this->telegram_username ?? 'User';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $roleVal = is_object($this->role) ? $this->role->value : (string) $this->role;
        return in_array($roleVal, ['admin', 'moderator']);
    }

    protected $fillable = [
        'phone',
        'email',
        'password',
        'telegram_id',
        'telegram_username',
        'role',
        'status',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            if ($user->profile) {
                foreach ($user->profile->documents as $document) {
                    $document->delete();
                }
                $user->profile->delete();
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function bookingsAsParent(): HasMany
    {
        return $this->hasMany(Booking::class, 'parent_id');
    }

    public function bookingsAsNanny(): HasMany
    {
        return $this->hasMany(Booking::class, 'nanny_id');
    }

    public function nannySlots(): HasMany
    {
        return $this->hasMany(NannySlot::class, 'nanny_id');
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function reviewsAsNanny(): HasMany
    {
        return $this->hasMany(\App\Models\Review::class, 'nanny_id');
    }
}
