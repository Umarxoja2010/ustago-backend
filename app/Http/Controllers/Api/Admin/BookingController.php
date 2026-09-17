<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with(['user', 'masterProfile', 'masterService.service', 'vehicle']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('masterProfileId')) {
            $query->where('master_profile_id', $request->integer('masterProfileId'));
        }

        if ($request->filled('userId')) {
            $query->where('user_id', $request->integer('userId'));
        }

        $bookings = $query->latest()->paginate($request->integer('perPage', 20))->withQueryString();

        return $this->ok([
            'items' => BookingResource::collection($bookings->items()),
            'meta' => [
                'page' => $bookings->currentPage(),
                'perPage' => $bookings->perPage(),
                'total' => $bookings->total(),
                'lastPage' => $bookings->lastPage(),
            ],
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['user', 'masterProfile', 'masterService.service', 'vehicle', 'statusLogs', 'review']);

        return $this->ok(new BookingResource($booking));
    }

    /**
     * Admin override — unlike the customer/mechanic status endpoint, this
     * isn't gated by the normal pending→accepted→completed transition
     * legality rules (see BookingService::transition) or ownership
     * policies. Admin needs the ability to resolve disputes (e.g. force-
     * cancel a stuck booking) that a normal user flow wouldn't allow.
     */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected', 'completed', 'cancelled'])],
        ]);

        $booking->update(['status' => $data['status']]);

        $booking->statusLogs()->create([
            'status' => $data['status'],
            'label' => 'Updated by admin',
            'happened_at' => now(),
        ]);

        $booking->load(['user', 'masterProfile', 'masterService.service', 'vehicle', 'statusLogs', 'review']);

        return $this->ok(new BookingResource($booking), 'Booking updated');
    }
}
