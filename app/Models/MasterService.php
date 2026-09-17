<?php

namespace App\Models;

use Database\Factories\MasterServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterService extends Model
{
    /** @use HasFactory<MasterServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'master_profile_id',
        'service_id',
        'duration',
        'price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
