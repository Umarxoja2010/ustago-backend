<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Notification;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    /**
     * Customer leaves a review on their own completed booking — one review
     * per booking (enforced here and by the DB unique constraint on
     * reviews.booking_id).
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $booking = Booking::with(['review', 'masterProfile'])->findOrFail($data['bookingId']);

        if ($booking->user_id !== $request->user()->id) {
            return $this->fail('errors.notYourBooking', [], 403);
        }

        if ($booking->status !== 'completed') {
            return $this->fail('errors.bookingNotCompleted', ['bookingId' => ['errors.bookingNotCompleted']]);
        }

        if ($booking->review !== null) {
            return $this->fail('errors.alreadyReviewed', ['bookingId' => ['errors.alreadyReviewed']]);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'master_profile_id' => $booking->master_profile_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $booking->masterProfile->recalculateRating();

        if ($booking->masterProfile->user_id) {
            Notification::create([
                'user_id' => $booking->masterProfile->user_id,
                'audience' => null,
                'title' => 'New review received',
                'body' => "{$request->user()->name} left a {$data['rating']}-star review",
                'kind' => 'review',
            ]);
        }

        return $this->ok(new ReviewResource($review), 'Review submitted', 201);
    }

    /** Public: visible reviews for a verified master, newest first. */
    public function forMaster(MasterProfile $master): JsonResponse
    {
        abort_if($master->verification_status !== 'verified', 404);

        $reviews = $master->reviews()
            ->where('hidden', false)
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->ok([
            'items' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'page' => $reviews->currentPage(),
                'perPage' => $reviews->perPage(),
                'total' => $reviews->total(),
                'lastPage' => $reviews->lastPage(),
            ],
        ]);
    }

    /** Mechanic's own workshop reviews, including hidden ones (so they know what got moderated). */
    public function mine(Request $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        $reviews = $profile->reviews()->with('user')->latest()->get();

        return $this->ok(ReviewResource::collection($reviews));
    }
}
