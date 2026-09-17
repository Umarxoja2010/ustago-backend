<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mechanic_can_add_service_to_own_workshop(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($mechanic, 'sanctum')->postJson('/api/master/services', [
            'serviceId' => $service->id,
            'duration' => '1 hour',
            'price' => 180000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.duration', '1 hour')
            ->assertJsonPath('data.price', 180000);

        $this->assertDatabaseHas('master_services', [
            'master_profile_id' => $profile->id,
            'service_id' => $service->id,
            'duration' => '1 hour',
            'price' => 180000,
        ]);
    }

    public function test_mechanic_cannot_edit_another_mechanics_service(): void
    {
        $owner = User::factory()->mechanic()->create();
        $ownerProfile = MasterProfile::factory()->for($owner)->create();
        $masterService = MasterService::factory()->for($ownerProfile, 'masterProfile')->create(['duration' => '1 hour']);

        $intruder = User::factory()->mechanic()->create();
        MasterProfile::factory()->for($intruder)->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/master/services/{$masterService->id}", ['duration' => '2 hours'])
            ->assertStatus(403);

        // duration is untouched — master service wasn't overridden by another mechanic
        $this->assertDatabaseHas('master_services', ['id' => $masterService->id, 'duration' => '1 hour']);
    }

    public function test_customer_cannot_manage_master_services(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'sanctum')->getJson('/api/master/services')->assertStatus(403);
    }

    public function test_cannot_add_the_same_catalog_service_twice(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        MasterProfile::factory()->for($mechanic)->create();
        $service = Service::factory()->create();

        $this->actingAs($mechanic, 'sanctum')->postJson('/api/master/services', [
            'serviceId' => $service->id, 'duration' => '30 min',
        ])->assertCreated();

        $this->actingAs($mechanic, 'sanctum')->postJson('/api/master/services', [
            'serviceId' => $service->id, 'duration' => '1 hour',
        ])->assertStatus(422)->assertJsonValidationErrors('serviceId');
    }

    public function test_mechanic_can_update_service_price_and_duration(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $service = Service::factory()->create();

        $masterService = MasterService::factory()->create([
            'master_profile_id' => $profile->id,
            'service_id' => $service->id,
            'duration' => '30 min',
            'price' => 100000,
        ]);

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/master/services/{$masterService->id}", [
                'duration' => '45 min',
                'price' => 150000,
            ])
            ->assertOk()
            ->assertJsonPath('data.duration', '45 min')
            ->assertJsonPath('data.price', 150000);

        $this->assertDatabaseHas('master_services', [
            'id' => $masterService->id,
            'duration' => '45 min',
            'price' => 150000,
        ]);
    }
}
