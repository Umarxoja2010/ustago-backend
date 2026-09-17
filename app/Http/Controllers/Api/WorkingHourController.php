<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkingHour\UpdateWorkingHoursRequest;
use App\Http\Resources\WorkingHourResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkingHourController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        return $this->ok(WorkingHourResource::collection($profile->workingHours()->orderBy('weekday')->get()));
    }

    /** Bulk-replace all 7 weekday rows in one call — matches the frontend's single "save hours" action. */
    public function update(UpdateWorkingHoursRequest $request): JsonResponse
    {
        $profile = $request->user()->masterProfile;
        abort_unless($profile, 404, 'errors.masterProfileNotFound');

        DB::transaction(function () use ($profile, $request) {
            foreach ($request->validated()['hours'] as $row) {
                $profile->workingHours()->updateOrCreate(
                    ['weekday' => $row['weekday']],
                    [
                        'open_time' => $row['closed'] ? null : $row['open'],
                        'close_time' => $row['closed'] ? null : $row['close'],
                        'closed' => $row['closed'],
                    ],
                );
            }
        });

        return $this->ok(WorkingHourResource::collection($profile->workingHours()->orderBy('weekday')->get()), 'Working hours updated');
    }
}
