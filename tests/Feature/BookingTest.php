<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeMasterService(): MasterService
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();

        return MasterService::factory()->for($profile, 'masterProfile')->create();
    }

    public function test_customer_can_create_booking(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(1, 'data.timeline');
    }

    public function test_double_booking_same_slot_is_rejected(): void
    {
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();
        $masterService = $this->makeMasterService();

        $payload = [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '11:00',
        ];

        $this->actingAs($customerA, 'sanctum')->postJson('/api/bookings', $payload)->assertCreated();

        $this->actingAs($customerB, 'sanctum')->postJson('/api/bookings', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('time');
    }

    public function test_cancelled_slot_frees_up_the_time(): void
    {
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();
        $masterService = $this->makeMasterService();

        $payload = [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '12:00',
        ];

        $first = $this->actingAs($customerA, 'sanctum')->postJson('/api/bookings', $payload)->assertCreated();
        $bookingId = $first->json('data.id');

        $this->actingAs($customerA, 'sanctum')
            ->patchJson("/api/bookings/{$bookingId}/status", ['status' => 'cancelled'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->actingAs($customerB, 'sanctum')->postJson('/api/bookings', $payload)->assertCreated();
    }

    public function test_mechanic_can_accept_then_complete_own_booking(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()->for($profile, 'masterProfile')->for($masterService, 'masterService')->create();

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertOk()->assertJsonPath('data.status', 'accepted');

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('master_profiles', ['id' => $profile->id, 'jobs_count' => 1]);
    }

    public function test_mechanic_cannot_manage_another_workshops_booking(): void
    {
        $owner = User::factory()->mechanic()->create();
        $ownerProfile = MasterProfile::factory()->for($owner)->create();
        $masterService = MasterService::factory()->for($ownerProfile, 'masterProfile')->create();
        $booking = Booking::factory()->for($ownerProfile, 'masterProfile')->for($masterService, 'masterService')->create();

        $intruder = User::factory()->mechanic()->create();
        MasterProfile::factory()->for($intruder)->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertStatus(403);
    }

    public function test_customer_cannot_accept_own_booking(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();
        $booking = Booking::factory()
            ->for($customer)
            ->for($masterService->masterProfile, 'masterProfile')
            ->for($masterService, 'masterService')
            ->create();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertStatus(403);
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $masterService = $this->makeMasterService();
        $booking = Booking::factory()
            ->for($owner)
            ->for($masterService->masterProfile, 'masterProfile')
            ->for($masterService, 'masterService')
            ->create();

        $this->actingAs($intruder, 'sanctum')->getJson("/api/bookings/{$booking->id}")->assertStatus(403);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()
            ->for($profile, 'masterProfile')
            ->for($masterService, 'masterService')
            ->create(['status' => 'completed']);

        // completed -> accepted is not a legal transition
        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertStatus(422);
    }

    public function test_customer_can_reschedule_a_pending_booking(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();

        $create = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '09:00',
        ])->assertCreated();

        $bookingId = $create->json('data.id');
        $newDate = now()->addDays(2)->toDateString();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/bookings/{$bookingId}/reschedule", ['date' => $newDate, 'time' => '13:00'])
            ->assertOk()
            ->assertJsonPath('data.date', $newDate)
            ->assertJsonPath('data.time', '13:00');
    }

    public function test_cannot_reschedule_into_an_occupied_slot(): void
    {
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();
        $masterService = $this->makeMasterService();
        $date = now()->addDay()->toDateString();

        $bookingA = $this->actingAs($customerA, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id, 'date' => $date, 'time' => '09:00',
        ])->assertCreated()->json('data.id');

        $this->actingAs($customerB, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id, 'date' => $date, 'time' => '10:00',
        ])->assertCreated();

        $this->actingAs($customerA, 'sanctum')
            ->patchJson("/api/bookings/{$bookingA}/reschedule", ['date' => $date, 'time' => '10:00'])
            ->assertStatus(422);
    }

    public function test_cannot_reschedule_an_accepted_booking(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()->for($profile, 'masterProfile')->for($masterService, 'masterService')->create();
        $customer = $booking->user;

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/reschedule", [
                'date' => now()->addDays(3)->toDateString(), 'time' => '11:00',
            ])
            ->assertStatus(422);
    }

    public function test_creating_a_booking_notifies_the_mechanic(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();
        $mechanicUserId = $masterService->masterProfile->user_id;

        $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '09:00',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mechanicUserId,
            'kind' => 'booking',
            'title' => 'New booking request',
        ]);
        // and nothing was sent to the customer for their own booking request
        $this->assertDatabaseMissing('notifications', ['user_id' => $customer->id]);
    }

    public function test_accepting_a_booking_notifies_the_customer(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()->for($profile, 'masterProfile')->for($masterService, 'masterService')->create();

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'accepted'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $booking->user_id,
            'kind' => 'booking',
            'title' => 'Booking accepted',
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $mechanic->id]);
    }

    public function test_rejecting_a_booking_notifies_the_customer(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()->for($profile, 'masterProfile')->for($masterService, 'masterService')->create();

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'rejected'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $booking->user_id,
            'kind' => 'booking',
            'title' => 'Booking rejected',
        ]);
    }

    public function test_completing_a_booking_notifies_the_customer(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();
        $booking = Booking::factory()->for($profile, 'masterProfile')->for($masterService, 'masterService')
            ->create(['status' => 'accepted']);

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $booking->user_id,
            'kind' => 'booking',
            'title' => 'Booking completed',
        ]);
    }

    public function test_cancelling_a_booking_notifies_the_mechanic(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();
        $mechanicUserId = $masterService->masterProfile->user_id;

        $create = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '09:00',
        ])->assertCreated();
        $bookingId = $create->json('data.id');

        // clear the "New booking request" notification so we cleanly assert only the cancel one below
        Notification::query()->delete();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/bookings/{$bookingId}/status", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mechanicUserId,
            'kind' => 'booking',
            'title' => 'Booking cancelled',
        ]);
    }

    public function test_rescheduling_a_booking_notifies_the_mechanic(): void
    {
        $customer = User::factory()->create();
        $masterService = $this->makeMasterService();
        $mechanicUserId = $masterService->masterProfile->user_id;

        $create = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
            'masterServiceId' => $masterService->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '09:00',
        ])->assertCreated();
        $bookingId = $create->json('data.id');

        Notification::query()->delete();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/bookings/{$bookingId}/reschedule", [
                'date' => now()->addDays(2)->toDateString(), 'time' => '13:00',
            ])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mechanicUserId,
            'kind' => 'booking',
            'title' => 'Booking rescheduled',
        ]);
    }
}
