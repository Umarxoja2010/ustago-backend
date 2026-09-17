<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\MasterServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshopName' => $this->workshop_name,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'verificationStatus' => $this->verification_status,
            'rating' => (float) $this->rating,
            'reviewCount' => $this->review_count,
            'jobsCount' => $this->jobs_count,
            'isOpen' => $this->is_open,
            'owner' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
                'email' => $this->user->email,
                'status' => $this->user->status,
            ]),
            'services' => MasterServiceResource::collection($this->whenLoaded('masterServices')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
