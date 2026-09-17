<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\VehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->ok(VehicleResource::collection($request->user()->vehicles()->latest()->get()));
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $vehicle = $request->user()->vehicles()->create($request->validated());

        return $this->ok(new VehicleResource($vehicle), 'Vehicle added', 201);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 403);

        $vehicle->update($request->validated());

        return $this->ok(new VehicleResource($vehicle->fresh()), 'Vehicle updated');
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 403);

        $vehicle->delete();

        return $this->ok(null, 'Vehicle removed');
    }
}
