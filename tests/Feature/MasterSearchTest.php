<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_masters_are_publicly_listed(): void
    {
        MasterProfile::factory()->create(['workshop_name' => 'Verified Servis']);
        MasterProfile::factory()->pending()->create(['workshop_name' => 'Pending Servis']);

        $response = $this->getJson('/api/masters')->assertOk();

        $names = collect($response->json('data.items'))->pluck('workshopName');
        $this->assertTrue($names->contains('Verified Servis'));
        $this->assertFalse($names->contains('Pending Servis'));
    }

    public function test_can_filter_by_service_name(): void
    {
        $master = MasterProfile::factory()->create();
        $service = Service::factory()->create(['name' => 'Brake repair']);
        MasterService::factory()->for($master, 'masterProfile')->for($service)->create(['active' => true]);

        $other = MasterProfile::factory()->create();
        $otherService = Service::factory()->create(['name' => 'Oil change '.uniqid()]);
        MasterService::factory()->for($other, 'masterProfile')->for($otherService)->create();

        $response = $this->getJson('/api/masters?service=Brake')->assertOk();

        $ids = collect($response->json('data.items'))->pluck('id');
        $this->assertTrue($ids->contains($master->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_unverified_master_profile_is_not_publicly_viewable(): void
    {
        $master = MasterProfile::factory()->pending()->create();

        $this->getJson("/api/masters/{$master->id}")->assertStatus(404);
    }

    public function test_can_sort_masters_by_nearest_distance(): void
    {
        $far = MasterProfile::factory()->create([
            'workshop_name' => 'Far Workshop',
            'lat' => 41.5000,
            'lng' => 69.5000,
        ]);

        $close = MasterProfile::factory()->create([
            'workshop_name' => 'Close Workshop',
            'lat' => 41.3150,
            'lng' => 69.2450,
        ]);

        $response = $this->getJson('/api/masters?lat=41.3110&lng=69.2405&sort=nearest')->assertOk();

        $items = $response->json('data.items');
        $this->assertEquals('Close Workshop', $items[0]['workshopName']);
        $this->assertNotNull($items[0]['distanceKm']);
        $this->assertLessThan($items[1]['distanceKm'], $items[0]['distanceKm']);
    }
}
