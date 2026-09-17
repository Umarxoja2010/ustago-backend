<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBroadcastRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /** History of platform broadcasts sent so far (user_id is always null for these — see store()). */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->whereNull('user_id')
            ->latest()
            ->paginate($request->integer('perPage', 20));

        return $this->ok([
            'items' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'page' => $notifications->currentPage(),
                'perPage' => $notifications->perPage(),
                'total' => $notifications->total(),
                'lastPage' => $notifications->lastPage(),
            ],
        ]);
    }

    /** Platform-wide broadcast to all users, or just customers / mechanics. */
    public function store(StoreBroadcastRequest $request): JsonResponse
    {
        $data = $request->validated();

        $notification = Notification::create([
            'user_id' => null,
            'audience' => $data['audience'],
            'title' => $data['title'],
            'body' => $data['body'],
            'kind' => $data['kind'] ?? 'announcement',
        ]);

        return $this->ok(new NotificationResource($notification), 'Broadcast sent', 201);
    }
}
