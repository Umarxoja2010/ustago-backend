<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPhone;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPhoneController extends Controller
{
    use ApiResponse;

    /**
     * List all phone numbers for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Hard-delete any phones whose 14-day grace period has expired
        $user->phones()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->delete();

        // Ensure user's primary phone exists in user_phones
        if (!empty($user->phone)) {
            $existing = $user->phones()->where('phone', $user->phone)->first();
            if (!$existing) {
                $user->phones()->create([
                    'phone' => $user->phone,
                    'is_primary' => true,
                ]);
            }
        }

        $phones = $user->phones()->orderByDesc('is_primary')->orderBy('id')->get();

        return $this->ok($this->formatPhones($phones));
    }

    /**
     * Add a new phone number (max 3 total).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $user = $request->user();
        $rawPhone = trim($request->string('phone'));

        // Format phone consistently (e.g. +998...)
        $phone = preg_replace('/[^\d+]/', '', $rawPhone);
        if (!str_starts_with($phone, '+') && str_starts_with($phone, '998')) {
            $phone = '+' . $phone;
        }

        // Clean expired
        $user->phones()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->delete();

        // Ensure user's primary phone exists in user_phones
        if (!empty($user->phone)) {
            $hasPrimary = $user->phones()->where('phone', $user->phone)->exists();
            if (!$hasPrimary) {
                $user->phones()->create([
                    'phone' => $user->phone,
                    'is_primary' => true,
                ]);
            }
        }

        $existing = $user->phones()->where('phone', $phone)->first();
        if ($existing) {
            if ($existing->scheduled_deletion_at !== null) {
                // Restore if was marked for deletion
                $existing->update(['scheduled_deletion_at' => null]);
                return $this->ok(
                    $this->formatPhones($user->phones()->orderByDesc('is_primary')->orderBy('id')->get()),
                    'Telefon raqam qayta faollashtirildi'
                );
            }

            return $this->fail('Bu telefon raqam allaqachon biriktirilgan', [], 422);
        }

        if ($user->phones()->count() >= 3) {
            return $this->fail("Ko'pi bilan 3 ta telefon raqam qo'shish mumkin", [], 422);
        }

        $isFirst = $user->phones()->count() === 0;

        $user->phones()->create([
            'phone' => $phone,
            'is_primary' => $isFirst,
        ]);

        if ($isFirst) {
            $user->update(['phone' => $phone]);
        }

        $phones = $user->phones()->orderByDesc('is_primary')->orderBy('id')->get();

        return $this->ok($this->formatPhones($phones), 'Telefon raqam muvaffaqiyatli qo\'shildi', 201);
    }

    /**
     * Mark a phone number for deletion in 14 days (security grace period).
     */
    public function destroy(Request $request, UserPhone $phone): JsonResponse
    {
        $user = $request->user();

        if ($phone->user_id !== $user->id) {
            return $this->fail('Ruxsat berilmagan', [], 403);
        }

        if ($phone->is_primary) {
            return $this->fail("Asosiy telefon raqamni o'chirib bo'lmaydi. Avval boshqa raqamni asosiy qilib belgilang.", [], 422);
        }

        // Schedule deletion in 14 days
        $scheduledAt = Carbon::now()->addDays(14);
        $phone->update([
            'scheduled_deletion_at' => $scheduledAt,
        ]);

        $phones = $user->phones()->orderByDesc('is_primary')->orderBy('id')->get();

        return $this->ok(
            $this->formatPhones($phones),
            "Xavfsizlik sababli telefon raqam 14 kundan keyin butunlay tizimdan o'chiriladi."
        );
    }

    /**
     * Cancel/restore a phone number scheduled for deletion.
     */
    public function restore(Request $request, UserPhone $phone): JsonResponse
    {
        $user = $request->user();

        if ($phone->user_id !== $user->id) {
            return $this->fail('Ruxsat berilmagan', [], 403);
        }

        $phone->update([
            'scheduled_deletion_at' => null,
        ]);

        $phones = $user->phones()->orderByDesc('is_primary')->orderBy('id')->get();

        return $this->ok(
            $this->formatPhones($phones),
            "Telefon raqamni o'chirish bekor qilindi va qayta faollashtirildi."
        );
    }

    /**
     * Make a phone number primary.
     */
    public function makePrimary(Request $request, UserPhone $phone): JsonResponse
    {
        $user = $request->user();

        if ($phone->user_id !== $user->id) {
            return $this->fail('Ruxsat berilmagan', [], 403);
        }

        if ($phone->scheduled_deletion_at !== null) {
            return $this->fail("O'chirilishi kutilayotgan raqamni asosiy qilib bo'lmaydi. Avval uni tiklang.", [], 422);
        }

        $user->phones()->update(['is_primary' => false]);
        $phone->update(['is_primary' => true]);
        $user->update(['phone' => $phone->phone]);

        $phones = $user->phones()->orderByDesc('is_primary')->orderBy('id')->get();

        return $this->ok(
            $this->formatPhones($phones),
            'Asosiy telefon raqam o\'zgartirildi'
        );
    }

    private function formatPhones($phones): array
    {
        return $phones->map(function (UserPhone $p) {
            $daysLeft = null;
            if ($p->scheduled_deletion_at) {
                $daysLeft = max(1, (int) ceil(now()->diffInSeconds($p->scheduled_deletion_at, false) / 86400));
            }

            return [
                'id' => $p->id,
                'phone' => $p->phone,
                'isPrimary' => (bool) $p->is_primary,
                'scheduledDeletionAt' => $p->scheduled_deletion_at ? $p->scheduled_deletion_at->toIso8601String() : null,
                'daysLeft' => $daysLeft,
            ];
        })->values()->toArray();
    }
}
