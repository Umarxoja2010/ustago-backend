<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshopName' => $this->workshop_name,
            'owner' => $this->owner_name,
            'about' => $this->about,
            'cover' => $this->cover,
            'logo' => $this->logo,
            'address' => $this->address,
            'district' => $this->district,
            'city' => $this->city,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'distanceKm' => isset($this->distance) ? round((float) $this->distance, 1) : null,
            'experienceYears' => $this->experience_years,
            'verificationStatus' => $this->verification_status,
            'rating' => (float) $this->rating,
            'reviewCount' => $this->review_count,
            'jobsCount' => $this->jobs_count,
            'isOpen' => $this->is_open,
            'phone' => $this->whenLoaded('user', fn () => $this->user?->phone),
            'services' => MasterServiceResource::collection($this->whenLoaded('masterServices')),
            'workingHours' => WorkingHourResource::collection($this->whenLoaded('workingHours')),
        ];
    }
}
