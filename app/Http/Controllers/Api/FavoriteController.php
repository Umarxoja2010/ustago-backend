<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterProfileResource;
use App\Models\MasterProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $masters = MasterProfile::query()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['masterServices' => fn ($q) => $q->where('active', true)->with('service')])
            ->get();

        return $this->ok(MasterProfileResource::collection($masters));
    }

    public function store(Request $request, MasterProfile $master): JsonResponse
    {
        $request->user()->favorites()->firstOrCreate(['master_profile_id' => $master->id]);

        return $this->ok(null, 'Added to favorites', 201);
    }

    public function destroy(Request $request, MasterProfile $master): JsonResponse
    {
        $request->user()->favorites()->where('master_profile_id', $master->id)->delete();

        return $this->ok(null, 'Removed from favorites');
    }
}
