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
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        $services = $profile->masterServices()->with('service')->get();

        return $this->ok(MasterServiceResource::collection($services));
    }

    public function store(StoreMasterServiceRequest $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

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

    private function authorizeOwnership(Request $request, MasterService $masterService): void
    {
        $profile = $request->user()->masterProfile;
        abort_if(! $profile || $masterService->master_profile_id !== $profile->id, 403);
    }
}
