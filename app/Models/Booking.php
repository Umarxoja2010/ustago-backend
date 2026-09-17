<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'master_profile_id',
        'master_service_id',
        'vehicle_id',
        'date',
        'time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function masterService(): BelongsTo
    {
        return $this->belongsTo(MasterService::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class)->orderBy('happened_at');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
