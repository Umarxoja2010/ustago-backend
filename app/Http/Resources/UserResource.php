<?php

namespace App\Http\Resources;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shaped to match the frontend's `AuthUser` type (src/lib/auth.tsx) so
 * phase 13 wiring is a drop-in replacement for the mock auth response.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MasterProfile|null $profile */
        $profile = $this->whenLoaded('masterProfile') ?: null;

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => ($this->email && ($this->role === 'admin' || !str_ends_with($this->email, '@ustago.local'))) ? $this->email : null,
            'phone' => $this->phone,
            'phones' => $this->relationLoaded('phones')
                ? $this->phones->map(fn ($p) => [
                    'id' => $p->id,
                    'phone' => $p->phone,
                    'isPrimary' => (bool) $p->is_primary,
                    'scheduledDeletionAt' => $p->scheduled_deletion_at?->toIso8601String(),
                    'daysLeft' => $p->scheduled_deletion_at ? max(1, (int) ceil(now()->diffInSeconds($p->scheduled_deletion_at, false) / 86400)) : null,
                ])->values()->toArray()
                : ($this->phone ? [[
                    'id' => 1,
                    'phone' => $this->phone,
                    'isPrimary' => true,
                    'scheduledDeletionAt' => null,
                    'daysLeft' => null,
                ]] : []),
            'role' => $this->role,
            'city' => $this->city,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'workshop' => $profile ? [
                'name' => $profile->workshop_name,
                'address' => $profile->address,
                'city' => $profile->city,
                'lat' => $profile->lat !== null ? (float) $profile->lat : null,
                'lng' => $profile->lng !== null ? (float) $profile->lng : null,
                'experience' => (string) $profile->experience_years,
                'services' => $profile->masterServices
                    ->where('active', true)
                    ->pluck('service.name')
                    ->filter()
                    ->implode(', ') ?: null,
                'verificationStatus' => $profile->verification_status,
            ] : null,
        ];
    }
}
