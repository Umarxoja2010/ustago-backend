<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'cta',
        'image',
        'link',
        'badge',
        'accent',
        'is_active',
        'sort_order',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }
}
