<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    /** Headline counts for the admin dashboard's summary cards. */
    public function overview(): JsonResponse
    {
        return $this->ok([
            'users' => [
                'total' => User::count(),
                'byRole' => User::query()->select('role', DB::raw('count(*) as count'))->groupBy('role')->pluck('count', 'role'),
                'byStatus' => User::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            ],
            'masters' => [
                'total' => MasterProfile::count(),
                'byVerificationStatus' => MasterProfile::query()
                    ->select('verification_status', DB::raw('count(*) as count'))
                    ->groupBy('verification_status')
                    ->pluck('count', 'verification_status'),
            ],
            'bookings' => [
                'total' => Booking::count(),
                'byStatus' => Booking::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            ],
            'reviews' => [
                'total' => Review::count(),
                'hidden' => Review::where('hidden', true)->count(),
                'platformAverageRating' => round((float) MasterProfile::where('review_count', '>', 0)->avg('rating'), 2) ?: 0,
            ],
        ]);
    }

    /** Daily new-signup counts, split by role, for the last N days (default 30). */
    public function signups(Request $request): JsonResponse
    {
        $days = min($request->integer('days', 30), 180);
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = User::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), 'role', DB::raw('count(*) as count'))
            ->groupBy('date', 'role')
            ->orderBy('date')
            ->get();

        return $this->ok($this->fillDateSeries($rows, $since, $days, ['customer', 'mechanic']));
    }

    /** Most-booked catalog services (by completed bookings), default top 5. */
    public function topServices(Request $request): JsonResponse
    {
        $limit = min($request->integer('limit', 5), 20);

        $rows = Service::query()
            ->join('master_services', 'master_services.service_id', '=', 'services.id')
            ->join('bookings', 'bookings.master_service_id', '=', 'master_services.id')
            ->where('bookings.status', 'completed')
            ->select('services.id', 'services.name', DB::raw('count(bookings.id) as bookings_count'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();

        return $this->ok($rows->map(fn ($r) => [
            'serviceId' => $r->id,
            'name' => $r->name,
            'bookingsCount' => (int) $r->bookings_count,
        ]));
    }

    private function fillDateSeries($rows, Carbon $since, int $days, array $roles): array
    {
        $byDateRole = $rows->groupBy('date');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $dayRows = $byDateRole->get($date, collect());

            $entry = ['date' => $date];
            foreach ($roles as $role) {
                $entry[$role] = (int) ($dayRows->firstWhere('role', $role)?->count ?? 0);
            }
            $series[] = $entry;
        }

        return $series;
    }
}
