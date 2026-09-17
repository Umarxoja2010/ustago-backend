<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMasterVerificationRequest;
use App\Http\Resources\Admin\AdminMasterResource;
use App\Models\MasterProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = MasterProfile::query()->with(['user', 'masterServices.service']);

        if ($request->filled('verificationStatus')) {
            $query->where('verification_status', $request->string('verificationStatus'));
        }

        if ($request->filled('q')) {
            $query->where('workshop_name', 'like', '%'.$request->string('q').'%');
        }

        $masters = $query->latest()->paginate($request->integer('perPage', 20))->withQueryString();

        return $this->ok([
            'items' => AdminMasterResource::collection($masters->items()),
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
        return $this->ok(new AdminMasterResource($master->load(['user', 'masterServices.service'])));
    }

    /** Verify, reject, or suspend a workshop — controls whether it's publicly listed. */
    public function updateVerification(UpdateMasterVerificationRequest $request, MasterProfile $master): JsonResponse
    {
        $master->update(['verification_status' => $request->validated()['verificationStatus']]);

        return $this->ok(new AdminMasterResource($master->fresh('user')), 'Verification status updated');
    }
}
