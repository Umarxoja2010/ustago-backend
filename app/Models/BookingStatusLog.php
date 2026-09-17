<?php

namespace App\Models;

use Database\Factories\BookingStatusLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusLog extends Model
{
    /** @use HasFactory<BookingStatusLogFactory> */
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['booking_id', 'status', 'label', 'happened_at'];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
