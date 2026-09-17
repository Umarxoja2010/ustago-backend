<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->with(['user', 'masterProfile']);

        if ($request->filled('masterProfileId')) {
            $query->where('master_profile_id', $request->integer('masterProfileId'));
        }

        if ($request->filled('hidden')) {
            $query->where('hidden', $request->boolean('hidden'));
        }

        $reviews = $query->latest()->paginate($request->integer('perPage', 20))->withQueryString();

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

    /** Hide/unhide a review — recalculates the workshop's rating since hidden reviews are excluded. */
    public function toggleHide(Review $review): JsonResponse
    {
        $review->update(['hidden' => ! $review->hidden]);
        $review->masterProfile->recalculateRating();

        return $this->ok(new ReviewResource($review->fresh(['user', 'masterProfile'])), 'Review updated');
    }
}
