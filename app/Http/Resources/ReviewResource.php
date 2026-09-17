<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookingId' => $this->booking_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'hidden' => $this->hidden,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'master' => $this->whenLoaded('masterProfile', fn () => [
                'id' => $this->masterProfile->id,
                'workshopName' => $this->masterProfile->workshop_name,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
