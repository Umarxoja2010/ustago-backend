<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cta' => $this->cta,
            'image' => $this->image,
            'link' => $this->link,
            'badge' => $this->badge,
            'accent' => $this->accent,
            'isActive' => (bool) $this->is_active,
            'sortOrder' => (int) $this->sort_order,
            'startDate' => $this->start_date?->toISOString(),
            'endDate' => $this->end_date?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
