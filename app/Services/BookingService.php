<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MasterService;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    private const LABELS = [
        'pending' => 'Booking requested',
        'accepted' => 'Confirmed by workshop',
        'rejected' => 'Rejected by workshop',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Create a booking for a customer, guarding against double-booking the
     * same master/date/time slot.
     */
    public function create(User $customer, array $data): Booking
    {
        $serviceId = $data['master_service_id'] ?? $data['masterServiceId'] ?? null;
        $vehicleId = $data['vehicle_id'] ?? $data['vehicleId'] ?? null;

        $masterService = MasterService::with('masterProfile')->findOrFail($serviceId);

        if (! $masterService->active) {
            throw ValidationException::withMessages([
                'masterServiceId' => 'errors.serviceUnavailable',
            ]);
        }

        if (! empty($vehicleId)) {
            $ownsVehicle = $customer->vehicles()->whereKey($vehicleId)->exists();
            if (! $ownsVehicle) {
                throw ValidationException::withMessages([
                    'vehicleId' => 'errors.vehicleNotFound',
                ]);
            }
        }

        $booking = DB::transaction(function () use ($customer, $data, $masterService, $vehicleId) {
            $masterService->masterProfile()->lockForUpdate()->firstOrFail();
            $this->assertSlotIsFree((int) $masterService->master_profile_id, (string) $data['date'], (string) $data['time']);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'master_profile_id' => $masterService->master_profile_id,
                'master_service_id' => $masterService->id,
                'vehicle_id' => $vehicleId,
                'date' => $data['date'],
                'time' => $data['time'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->statusLogs()->create([
                'status' => 'pending',
                'label' => self::LABELS['pending'],
                'happened_at' => now(),
            ]);

            return $booking;
        });

        $this->notifyMechanic(
            $booking,
            'New booking request',
            "{$customer->name} requested {$masterService->service?->name} for {$data['date']} {$data['time']}",
        );

        return $booking;
    }

    /**
     * Apply a status transition, validating it's legal for the current
     * status, and append a timeline entry.
     */
    public function transition(Booking $booking, string $newStatus): Booking
    {
        $booking = DB::transaction(function () use ($booking, $newStatus) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $allowed = [
                'pending' => ['accepted', 'rejected', 'cancelled'],
                'accepted' => ['completed', 'cancelled'],
            ];

            if (! in_array($newStatus, $allowed[$booking->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'errors.invalidStatusTransition',
                ]);
            }

            $booking->update(['status' => $newStatus]);
            $booking->statusLogs()->create([
                'status' => $newStatus,
                'label' => self::LABELS[$newStatus] ?? ucfirst($newStatus),
                'happened_at' => now(),
            ]);

            if ($newStatus === 'completed') {
                $booking->masterProfile()->increment('jobs_count');
            }

            return $booking;
        });

        $booking->loadMissing('masterProfile', 'user');

        if (in_array($newStatus, ['accepted', 'rejected', 'completed'], true)) {
            $this->notifyCustomer($booking, "Booking {$newStatus}", self::LABELS[$newStatus] ?? ucfirst($newStatus));
        } elseif ($newStatus === 'cancelled') {
            $this->notifyMechanic($booking, 'Booking cancelled', "{$booking->user?->name} cancelled their booking");
        }

        return $booking->fresh();
    }

    /**
     * Customer moves a booking to a new date/time — only while still `pending`.
     */
    public function reschedule(Booking $booking, string $date, string $time): Booking
    {
        if ($booking->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'errors.onlyPendingCanReschedule',
            ]);
        }

        DB::transaction(function () use ($booking, $date, $time) {
            $booking->masterProfile()->lockForUpdate()->firstOrFail();
            $this->assertSlotIsFree((int) $booking->master_profile_id, (string) $date, (string) $time, $booking->id);

            $booking->update(['date' => $date, 'time' => $time]);

            $booking->statusLogs()->create([
                'status' => 'pending',
                'label' => 'Rescheduled',
                'happened_at' => now(),
            ]);
        });

        $booking->loadMissing('user');
        $this->notifyMechanic($booking, 'Booking rescheduled', "{$booking->user?->name} moved their booking to {$date} {$time}");

        return $booking->fresh();
    }

    private function notifyMechanic(Booking $booking, string $title, string $body): void
    {
        $booking->loadMissing('masterProfile');
        $mechanicUserId = $booking->masterProfile?->user_id;
        if (! $mechanicUserId) {
            return;
        }

        Notification::create([
            'user_id' => $mechanicUserId,
            'audience' => null,
            'title' => $title,
            'body' => $body,
            'kind' => 'booking',
        ]);
    }

    private function notifyCustomer(Booking $booking, string $title, string $body): void
    {
        Notification::create([
            'user_id' => $booking->user_id,
            'audience' => null,
            'title' => $title,
            'body' => $body,
            'kind' => 'booking',
        ]);
    }

    private function assertSlotIsFree(int $masterProfileId, string $date, string $time, ?int $excludeBookingId = null): void
    {
        $cleanTime = substr(trim($time), 0, 5); // Masalan: "10:00"

        $conflict = Booking::query()
            ->where('master_profile_id', $masterProfileId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'accepted'])
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->where(function ($q) use ($time, $cleanTime) {
                $q->where('time', $time)
                    ->orWhere('time', $cleanTime)
                    ->orWhere('time', "{$cleanTime}:00")
                    ->orWhereRaw('substr(time, 1, 5) = ?', [$cleanTime]);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'time' => 'errors.slotUnavailable',
            ]);
        }
    }
}
