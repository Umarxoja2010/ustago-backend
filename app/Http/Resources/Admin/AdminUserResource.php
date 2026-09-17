<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'city' => $this->city,
            'status' => $this->status,
            'workshopName' => $this->whenLoaded('masterProfile', fn () => $this->masterProfile?->workshop_name),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
