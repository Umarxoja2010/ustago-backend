<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'master_profile_id',
        'rating',
        'comment',
        'hidden',
    ];

    protected function casts(): array
    {
        return [
            'hidden' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}
