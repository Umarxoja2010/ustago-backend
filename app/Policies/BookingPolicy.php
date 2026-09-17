<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'customer') {
            return $booking->user_id === $user->id;
        }

        if ($user->role === 'mechanic') {
            return $booking->masterProfile?->user_id === $user->id;
        }

        return false;
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Mexanik o'z ustaxonasidagi buyurtmalarni boshqaradi
        if ($user->role === 'mechanic' && $booking->masterProfile?->user_id === $user->id) {
            return true;
        }

        // Mijoz faqat o'z buyurtmasini bekor (cancel) qila oladi
        if ($user->role === 'customer' && $booking->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function manage(User $user, Booking $booking): bool
    {
        return $this->updateStatus($user, $booking);
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }
}
