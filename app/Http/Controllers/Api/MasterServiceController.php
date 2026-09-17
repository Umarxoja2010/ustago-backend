<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterService\StoreMasterServiceRequest;
use App\Http\Requests\MasterService\UpdateMasterServiceRequest;
use App\Http\Resources\MasterServiceResource;
use App\Models\MasterService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterServiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $profile = $this->ensureProfile($request);

        $services = $profile->masterServices()->with('service')->get();

        return $this->ok(MasterServiceResource::collection($services));
    }

    public function store(StoreMasterServiceRequest $request): JsonResponse
    {
        $profile = $this->ensureProfile($request);

        $data = $request->validated();

        $masterService = $profile->masterServices()->create([
            'service_id' => $data['serviceId'],
            'duration' => $data['duration'] ?? null,
            'price' => $data['price'] ?? null,
            'active' => $data['active'] ?? true,
        ]);

        return $this->ok(new MasterServiceResource($masterService->load('service')), 'Service added', 201);
    }

    public function update(UpdateMasterServiceRequest $request, MasterService $masterService): JsonResponse
    {
        $this->authorizeOwnership($request, $masterService);

        $masterService->update($request->validated());

        return $this->ok(new MasterServiceResource($masterService->fresh('service')), 'Service updated');
    }

    public function destroy(Request $request, MasterService $masterService): JsonResponse
    {
        $this->authorizeOwnership($request, $masterService);

        $masterService->delete();

        return $this->ok(null, 'Service removed');
    }

    private function ensureProfile(Request $request)
    {
        $user = $request->user();
        return \App\Models\MasterProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'workshop_name' => $user->name,
                'address' => $user->city ?? 'Toshkent',
                'city' => $user->city ?? 'Toshkent',
                'lat' => 41.311081,
                'lng' => 69.240562,
                'verification_status' => 'pending',
            ]
        );
    }

    private function authorizeOwnership(Request $request, MasterService $masterService): void
    {
        $profile = $this->ensureProfile($request);
        abort_if($masterService->master_profile_id !== $profile->id, 403);
    }
}
