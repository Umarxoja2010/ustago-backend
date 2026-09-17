<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::query()->get();

        if ($services->isEmpty()) {
            $this->call(ServiceSeeder::class);
            $services = Service::query()->get();
        }
        $customers = collect(range(1, 50))->map(function (int $number): User {
            $customer = User::factory()->create([
                'name' => "Demo Customer {$number}",
                'email' => "customer{$number}@ustago.local",
            ]);

            Vehicle::factory()->create(['user_id' => $customer->id]);

            return $customer;
        });

        $masterServices = collect();

        collect(range(1, 10))->each(function (int $number) use ($services, &$masterServices): void {
            $mechanic = User::factory()->mechanic()->create([
                'name' => "Demo Mechanic {$number}",
                'email' => "mechanic{$number}@ustago.local",
            ]);

            $profile = MasterProfile::factory()->create([
                'user_id' => $mechanic->id,
                'workshop_name' => "UstaGo Workshop {$number}",
                'owner_name' => $mechanic->name,
            ]);

            $services->take(3)->each(function (Service $service) use ($profile, &$masterServices): void {
                $masterServices->push(MasterService::factory()->create([
                    'master_profile_id' => $profile->id,
                    'service_id' => $service->id,
                ]));
            });
        });

        $masterServices = $masterServices->values();

        collect(range(1, 100))->each(function (int $number) use ($customers, $masterServices): void {
            $customer = $customers->random();
            $masterService = $masterServices->random();
            $completed = $number <= 60;

            $booking = Booking::factory()->create([
                'user_id' => $customer->id,
                'master_profile_id' => $masterService->master_profile_id,
                'master_service_id' => $masterService->id,
                'vehicle_id' => $customer->vehicles()->first()?->id,
                'date' => now()->subDays($completed ? fake()->numberBetween(1, 90) : fake()->numberBetween(-14, 14))->toDateString(),
                'status' => $completed ? 'completed' : fake()->randomElement(['pending', 'accepted', 'cancelled']),
            ]);

            if ($completed) {
                Review::factory()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $customer->id,
                    'master_profile_id' => $masterService->master_profile_id,
                ]);
            }
        });

        MasterProfile::query()->each(function (MasterProfile $profile): void {
            $profile->recalculateRating();
            $profile->update([
                'jobs_count' => $profile->bookings()->where('status', 'completed')->count(),
            ]);
        });
    }
}
