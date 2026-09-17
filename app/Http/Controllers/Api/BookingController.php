<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->with(['masterProfile.user', 'masterService.service', 'vehicle', 'statusLogs'])
            ->when($user->role === 'customer', fn ($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'mechanic', fn ($q) => $q->where('master_profile_id', $user->masterProfile?->id))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return BookingResource::collection($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'master_service_id' => 'required_without:masterServiceId|integer|exists:master_services,id',
            'masterServiceId' => 'required_without:master_service_id|integer|exists:master_services,id',
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'vehicleId' => 'nullable|integer|exists:vehicles,id',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $booking = $this->bookingService->create($request->user(), $validated);

        return (new BookingResource($booking->load(['masterProfile.user', 'masterService.service', 'vehicle', 'statusLogs'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load(['masterProfile.user', 'masterService.service', 'vehicle', 'statusLogs']));
    }

    public function updateStatus(Request $request, Booking $booking): BookingResource
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'required|string|in:accepted,rejected,completed,cancelled',
        ]);

        // Mijoz faqat bekor (cancel) qila oladi, qabul yoki yakunlay olmaydi
        if ($user->role === 'customer') {
            if ($booking->user_id !== $user->id || $validated['status'] !== 'cancelled') {
                abort(403);
            }
        } elseif ($user->role === 'mechanic') {
            if ($booking->masterProfile?->user_id !== $user->id) {
                abort(403);
            }
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        $updated = $this->bookingService->transition($booking, $validated['status']);

        return new BookingResource($updated->load(['masterProfile.user', 'masterService.service', 'vehicle', 'statusLogs']));
    }

    public function reschedule(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('reschedule', $booking);

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|string',
        ]);

        $updated = $this->bookingService->reschedule($booking, $validated['date'], $validated['time']);

        return new BookingResource($updated->load(['masterProfile.user', 'masterService.service', 'vehicle', 'statusLogs']));
    }
}
