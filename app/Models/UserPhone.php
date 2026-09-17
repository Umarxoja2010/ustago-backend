<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'is_primary',
        'scheduled_deletion_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'scheduled_deletion_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isScheduledForDeletion(): bool
    {
        return $this->scheduled_deletion_at !== null;
    }
}
