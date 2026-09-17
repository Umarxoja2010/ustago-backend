<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'date' => $this->date?->toDateString(),
            'time' => substr((string) $this->time, 0, 5),
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'master' => $this->whenLoaded('masterProfile', fn () => [
                'id' => $this->masterProfile->id,
                'workshopName' => $this->masterProfile->workshop_name,
                'phone' => $this->masterProfile->user?->phone,
                'address' => $this->masterProfile->address,
            ]),
            'service' => $this->whenLoaded('masterService', fn () => [
                'id' => $this->masterService->id,
                'name' => $this->masterService->service?->name,
            ]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'label' => trim("{$this->vehicle->brand} {$this->vehicle->model} · {$this->vehicle->plate}"),
            ] : null),
            'hasReview' => $this->whenLoaded('review', fn () => $this->review !== null),
            'timeline' => BookingStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
