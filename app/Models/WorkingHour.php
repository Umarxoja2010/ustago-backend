<?php

namespace App\Models;

use Database\Factories\WorkingHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    /** @use HasFactory<WorkingHourFactory> */
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'master_profile_id',
        'weekday',
        'open_time',
        'close_time',
        'closed',
    ];

    protected function casts(): array
    {
        return [
            'closed' => 'boolean',
        ];
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}
