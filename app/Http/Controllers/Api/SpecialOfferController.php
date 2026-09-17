<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecialOfferResource;
use App\Models\SpecialOffer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SpecialOfferController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $offers = SpecialOffer::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ok(SpecialOfferResource::collection($offers));
    }
}
