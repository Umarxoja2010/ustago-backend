<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_manage_own_vehicles(): void
    {
        $customer = User::factory()->create();

        $create = $this->actingAs($customer, 'sanctum')->postJson('/api/vehicles', [
            'brand' => 'Kia', 'model' => 'Cerato', 'year' => '2019', 'plate' => '01A123BC',
        ]);
        $create->assertCreated()->assertJsonPath('success', true);
        $vehicleId = $create->json('data.id');

        $this->actingAs($customer, 'sanctum')->getJson('/api/vehicles')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($customer, 'sanctum')->patchJson("/api/vehicles/{$vehicleId}", ['color' => 'black'])
            ->assertOk()->assertJsonPath('data.color', 'black');

        $this->actingAs($customer, 'sanctum')->deleteJson("/api/vehicles/{$vehicleId}")->assertOk();
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_customer_cannot_edit_another_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/vehicles/{$vehicle->id}", ['color' => 'red'])
            ->assertStatus(403);
    }

    public function test_mechanic_cannot_access_vehicle_endpoints(): void
    {
        $mechanic = User::factory()->mechanic()->create();

        $this->actingAs($mechanic, 'sanctum')->getJson('/api/vehicles')->assertStatus(403);
    }
}
