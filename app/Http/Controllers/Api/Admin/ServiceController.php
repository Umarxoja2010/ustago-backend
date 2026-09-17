<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\MasterService;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    use ApiResponse;

    /** Manages the global service catalog (search facets + what a mechanic can offer). */
    public function index(): JsonResponse
    {
        return $this->ok(ServiceResource::collection(Service::orderBy('name')->get()));
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $service = Service::create([
            ...$data,
            'slug' => Str::slug($data['name']),
        ]);

        return $this->ok(new ServiceResource($service), 'Service created', 201);
    }

    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $service->update($data);

        return $this->ok(new ServiceResource($service->fresh()), 'Service updated');
    }

    /** Blocked if any mechanic currently offers it — deleting would silently strip their pricing. */
    public function destroy(Service $service): JsonResponse
    {
        if (MasterService::where('service_id', $service->id)->exists()) {
            return $this->fail('errors.serviceInUse', [], 409);
        }

        $service->delete();

        return $this->ok(null, 'Service removed');
    }
}
