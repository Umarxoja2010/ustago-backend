<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\MasterSearchRequest;
use App\Http\Resources\MasterProfileResource;
use App\Models\MasterProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class MasterController extends Controller
{
    use ApiResponse;

    /**
     * Public master search/listing — filter by name, location, service,
     * price range, and minimum rating; sortable; paginated.
     */
    public function index(MasterSearchRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = MasterProfile::query()
            ->with(['masterServices' => fn ($q) => $q->where('active', true)->with('service')])
            ->where('verification_status', 'verified')
            ->without('user'); // Hide phone numbers from listings

        if (! empty($filters['q'])) {
            $rawTerm = trim($filters['q']);
            $term = $rawTerm;
            $cleanChars = preg_split('//u', preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($rawTerm)), -1, PREG_SPLIT_NO_EMPTY);
            $loosePattern = count($cleanChars) >= 3 ? ('%' . implode('%', $cleanChars) . '%') : null;

            $query->where(function ($q) use ($term, $loosePattern) {
                $q->where('workshop_name', 'like', '%'.$term.'%')
                    ->orWhere('owner_name', 'like', '%'.$term.'%')
                    ->orWhere('about', 'like', '%'.$term.'%')
                    ->orWhere('address', 'like', '%'.$term.'%')
                    ->orWhere('city', 'like', '%'.$term.'%')
                    ->orWhere('district', 'like', '%'.$term.'%')
                    ->orWhereHas('masterServices.service', function ($sq) use ($term) {
                        $sq->where('name', 'like', '%'.$term.'%');
                    });

                if ($loosePattern) {
                    $q->orWhere('workshop_name', 'like', $loosePattern)
                        ->orWhere('owner_name', 'like', $loosePattern)
                        ->orWhere('about', 'like', $loosePattern);
                }
            });
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (! empty($filters['service'])) {
            $service = trim($filters['service']);
            $query->where(function ($sq) use ($service) {
                $sq->whereHas('masterServices', function ($q) use ($service) {
                    $q->where('active', true)
                        ->whereHas('service', fn ($s) => $s->where('name', 'like', '%'.$service.'%'));
                })->orWhere('about', 'like', '%'.$service.'%');
            });
        }

        if (! empty($filters['minRating'])) {
            $query->where('rating', '>=', $filters['minRating']);
        }

        $userLat = isset($filters['lat']) ? (float) $filters['lat'] : null;
        $userLng = isset($filters['lng']) ? (float) $filters['lng'] : null;

        $sort = $filters['sort'] ?? 'rating';
        if ($sort === 'nearest' || $sort === 'distance') {
            // Default to Tashkent center if coordinates were not provided
            $userLat = $userLat ?? 41.311081;
            $userLng = $userLng ?? 69.240562;
        }

        if ($userLat !== null && $userLng !== null) {
            $haversine = "(6371 * acos(min(max(cos(radians({$userLat})) * cos(radians(lat)) * cos(radians(lng) - radians({$userLng})) + sin(radians({$userLat})) * sin(radians(lat)), -1.0), 1.0)))";
            $query->selectRaw("master_profiles.*, {$haversine} AS distance");

            if (! empty($filters['maxDistance'])) {
                $query->whereRaw("{$haversine} <= ?", [(float) $filters['maxDistance']]);
            }
        }

        if (($sort === 'nearest' || $sort === 'distance') && $userLat !== null && $userLng !== null) {
            $query->orderByRaw("CASE WHEN lat IS NULL OR lng IS NULL THEN 1 ELSE 0 END ASC")
                ->orderBy('distance', 'asc');
        } elseif ($sort === 'name') {
            $query->orderBy('workshop_name');
        } else {
            $query->orderByDesc('rating');
        }

        $masters = $query->paginate($filters['perPage'] ?? 15)->withQueryString();

        return $this->ok([
            'items' => MasterProfileResource::collection($masters->items()),
            'meta' => [
                'page' => $masters->currentPage(),
                'perPage' => $masters->perPage(),
                'total' => $masters->total(),
                'lastPage' => $masters->lastPage(),
            ],
        ]);
    }

    public function show(MasterProfile $master): JsonResponse
    {
        abort_if($master->verification_status !== 'verified', 404);

        $master->load([
            'user',
            'masterServices' => fn ($q) => $q->where('active', true)->with('service'),
            'workingHours',
        ]);

        return $this->ok(new MasterProfileResource($master));
    }
}
