<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedBooking(User $customer): Booking
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create(['rating' => 0, 'review_count' => 0]);
        $masterService = MasterService::factory()->for($profile, 'masterProfile')->create();

        return Booking::factory()
            ->for($customer)
            ->for($profile, 'masterProfile')
            ->for($masterService, 'masterService')
            ->create(['status' => 'completed']);
    }

    public function test_customer_can_review_own_completed_booking_and_rating_rolls_up(): void
    {
        $customer = User::factory()->create();
        $booking = $this->completedBooking($customer);

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id,
            'rating' => 5,
            'comment' => 'Great service',
        ]);

        $response->assertCreated()->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('master_profiles', [
            'id' => $booking->master_profile_id,
            'rating' => 5,
            'review_count' => 1,
        ]);
    }

    public function test_cannot_review_a_booking_that_is_not_completed(): void
    {
        $customer = User::factory()->create();
        $booking = $this->completedBooking($customer);
        $booking->update(['status' => 'accepted']);

        $this->actingAs($customer, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id, 'rating' => 4,
        ])->assertStatus(422);
    }

    public function test_cannot_review_the_same_booking_twice(): void
    {
        $customer = User::factory()->create();
        $booking = $this->completedBooking($customer);

        $this->actingAs($customer, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id, 'rating' => 5,
        ])->assertCreated();

        $this->actingAs($customer, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id, 'rating' => 3,
        ])->assertStatus(422);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_cannot_review_someone_elses_booking(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $booking = $this->completedBooking($owner);

        $this->actingAs($intruder, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id, 'rating' => 1,
        ])->assertStatus(403);
    }

    public function test_hidden_reviews_are_excluded_from_public_master_listing(): void
    {
        $master = MasterProfile::factory()->create();
        Review::factory()->for($master, 'masterProfile')->create(['hidden' => false, 'comment' => 'visible one']);
        Review::factory()->for($master, 'masterProfile')->create(['hidden' => true, 'comment' => 'hidden one']);

        $response = $this->getJson("/api/masters/{$master->id}/reviews")->assertOk();

        $comments = collect($response->json('data.items'))->pluck('comment');
        $this->assertTrue($comments->contains('visible one'));
        $this->assertFalse($comments->contains('hidden one'));
    }

    public function test_mechanic_sees_own_hidden_reviews_too(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $profile = MasterProfile::factory()->for($mechanic)->create();
        Review::factory()->for($profile, 'masterProfile')->create(['hidden' => true]);

        $response = $this->actingAs($mechanic, 'sanctum')->getJson('/api/master/reviews')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_submitting_a_review_notifies_the_mechanic(): void
    {
        $customer = User::factory()->create();
        $booking = $this->completedBooking($customer);
        $mechanicUserId = $booking->masterProfile->user_id;

        $this->actingAs($customer, 'sanctum')->postJson('/api/reviews', [
            'bookingId' => $booking->id,
            'rating' => 5,
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $mechanicUserId,
            'kind' => 'review',
            'title' => 'New review received',
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $customer->id]);
    }
}
