<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DocumentType;
use App\Enums\DocumentStatus;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'type',
        'file_path',
        'status',
        'rejection_reason',
        'verified_at',
        'verified_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($document) {
            \App\Jobs\ProcessDocumentJob::dispatch($document);
        });

        static::updated(function ($document) {
            if ($document->wasChanged('status')) {
                $user = $document->profile?->user;
                if ($user) {
                    $user->notify(new \App\Notifications\DocumentStatusChangedNotification($document));
                }
            }
        });

        static::deleted(function ($document) {
            if ($document->file_path) {
                \Illuminate\Support\Facades\Storage::disk(config('filesystems.default', 'public'))->delete($document->file_path);
            }
            $profile = $document->profile;
            if ($profile) {
                $hasApproved = $profile->documents()->where('status', \App\Enums\DocumentStatus::APPROVED)->exists();
                if (!$hasApproved) {
                    $profile->update(['is_verified' => false]);
                }
            }
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
