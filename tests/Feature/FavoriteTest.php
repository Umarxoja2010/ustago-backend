<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_favorite_and_unfavorite_a_master(): void
    {
        $customer = User::factory()->create();
        $master = MasterProfile::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/masters/{$master->id}/favorite")
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')->getJson('/api/favorites')
            ->assertOk()->assertJsonCount(1, 'data');

        // Idempotent — favoriting twice doesn't duplicate
        $this->actingAs($customer, 'sanctum')->postJson("/api/masters/{$master->id}/favorite")->assertCreated();
        $this->assertDatabaseCount('favorites', 1);

        $this->actingAs($customer, 'sanctum')->deleteJson("/api/masters/{$master->id}/favorite")->assertOk();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_mechanic_cannot_use_favorite_endpoints(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $master = MasterProfile::factory()->create();

        $this->actingAs($mechanic, 'sanctum')
            ->postJson("/api/masters/{$master->id}/favorite")
            ->assertStatus(403);
    }
}
