<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UpdateMasterProfileRequest;
use App\Http\Resources\MasterProfileResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated mechanic managing their own workshop profile.
 * (Public browsing/search lives in MasterController.)
 */
class MasterProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        return $this->ok(new MasterProfileResource($profile->load(['masterServices.service', 'workingHours'])));
    }

    public function update(UpdateMasterProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        $profile->update($request->mapped());

        return $this->ok(new MasterProfileResource($profile->fresh(['masterServices.service', 'workingHours'])), 'Profile updated');
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        $request->validate([
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'cover' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $updates = [];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('masters/logos', 'public');
            $updates['logo'] = asset('storage/'.$path);
            $request->user()->update(['avatar' => $updates['logo']]);
        }

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('masters/covers', 'public');
            $updates['cover'] = asset('storage/'.$path);
        }

        if (! empty($updates)) {
            $profile->update($updates);
        }

        return $this->ok(new MasterProfileResource($profile->fresh(['masterServices.service', 'workingHours'])), 'Photo uploaded successfully');
    }
}
