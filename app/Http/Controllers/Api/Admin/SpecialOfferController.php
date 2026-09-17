<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpecialOfferRequest;
use App\Http\Resources\SpecialOfferResource;
use App\Models\SpecialOffer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SpecialOfferController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $offers = SpecialOffer::orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ok(SpecialOfferResource::collection($offers));
    }

    public function store(SpecialOfferRequest $request): JsonResponse
    {
        $data = $request->validated();
        $offer = SpecialOffer::create($data);

        return $this->ok(new SpecialOfferResource($offer), 'Offer created', 201);
    }

    public function show(SpecialOffer $offer): JsonResponse
    {
        return $this->ok(new SpecialOfferResource($offer));
    }

    public function update(SpecialOfferRequest $request, SpecialOffer $offer): JsonResponse
    {
        $data = $request->validated();
        $offer->update($data);

        return $this->ok(new SpecialOfferResource($offer->fresh()), 'Offer updated');
    }

    public function toggle(SpecialOffer $offer): JsonResponse
    {
        $offer->update(['is_active' => !$offer->is_active]);

        return $this->ok(new SpecialOfferResource($offer->fresh()), 'Offer status updated');
    }

    public function destroy(SpecialOffer $offer): JsonResponse
    {
        $offer->delete();

        return $this->ok(null, 'Offer removed');
    }
}
