<?php

namespace App\Models;

use Database\Factories\MasterProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProfile extends Model
{
    /** @use HasFactory<MasterProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workshop_name',
        'owner_name',
        'address',
        'district',
        'city',
        'about',
        'cover',
        'logo',
        'lat',
        'lng',
        'experience_years',
        'verification_status',
        'rating',
        'review_count',
        'jobs_count',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'rating' => 'decimal:2',
            'is_open' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterServices(): HasMany
    {
        return $this->hasMany(MasterService::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Recalculate rating/review_count from visible reviews.
     * Called after a review is created/hidden/deleted.
     */
    public function recalculateRating(): void
    {
        $visible = $this->reviews()->where('hidden', false);
        $this->update([
            'rating' => round((float) $visible->avg('rating'), 2) ?: 0,
            'review_count' => $visible->count(),
        ]);
    }
}
