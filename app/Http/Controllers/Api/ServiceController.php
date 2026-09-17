<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public read-only listing of the global service catalog — needed so a
 * mechanic can pick which catalog service to price when adding to their own
 * `master_services` (see MasterServiceController::store, which requires a
 * valid serviceId). Catalog mutation stays admin-only (Api\Admin\ServiceController).
 */
class ServiceController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        if (Service::count() === 0) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'ServiceSeeder',
                '--force' => true,
            ]);
        }

        return $this->ok(ServiceResource::collection(Service::orderBy('name')->get()));
    }
}
