<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /** Personal notifications plus platform broadcasts matching the user's role. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $audience = $user->isMechanic() ? 'mechanics' : 'customers';

        $notifications = Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)
                ->orWhere(fn ($b) => $b->whereNull('user_id')->whereIn('audience', ['all', $audience])))
            ->latest()
            ->get();

        return $this->ok(NotificationResource::collection($notifications));
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== null && $notification->user_id !== $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $this->ok(new NotificationResource($notification));
    }
}
