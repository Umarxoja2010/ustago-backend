<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'weekday' => $this->weekday,
            'open' => $this->closed ? null : substr((string) $this->open_time, 0, 5),
            'close' => $this->closed ? null : substr((string) $this->close_time, 0, 5),
            'closed' => $this->closed,
        ];
    }
}
