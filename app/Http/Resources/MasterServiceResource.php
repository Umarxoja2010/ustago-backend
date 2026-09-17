<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serviceId' => $this->service_id,
            'name' => $this->service?->name,
            'icon' => $this->service?->icon,
            'duration' => $this->duration,
            'price' => $this->price !== null ? (int) $this->price : null,
            'active' => $this->active,
        ];
    }
}
