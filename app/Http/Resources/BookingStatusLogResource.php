<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingStatusLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'label' => $this->label,
            'happenedAt' => $this->happened_at?->toIso8601String(),
        ];
    }
}
