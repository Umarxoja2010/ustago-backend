<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingHourTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $hours = [];
        for ($day = 0; $day <= 6; $day++) {
            $hours[] = [
                'weekday' => $day,
                'open' => $day === 6 ? null : '09:00',
                'close' => $day === 6 ? null : '18:00',
                'closed' => $day === 6, // Sunday closed
            ];
        }

        return ['hours' => $hours];
    }

    public function test_mechanic_can_bulk_set_working_hours(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        MasterProfile::factory()->for($mechanic)->create();

        $response = $this->actingAs($mechanic, 'sanctum')->putJson('/api/master/working-hours', $this->payload());

        $response->assertOk()->assertJsonCount(7, 'data');
        $this->assertDatabaseCount('working_hours', 7);
    }

    public function test_rejects_incomplete_week(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        MasterProfile::factory()->for($mechanic)->create();

        $payload = $this->payload();
        array_pop($payload['hours']); // only 6 days

        $this->actingAs($mechanic, 'sanctum')
            ->putJson('/api/master/working-hours', $payload)
            ->assertStatus(422);
    }
}
