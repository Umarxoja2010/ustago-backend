<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'sanctum')->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_admin_can_list_and_suspend_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users')
            ->assertOk()->assertJsonPath('data.meta.total', 2); // admin + target

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$target->id}/status", ['status' => 'suspended'])
            ->assertOk()->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'suspended']);
    }

    public function test_admin_cannot_modify_another_admin_status(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$otherAdmin->id}/status", ['status' => 'suspended'])
            ->assertStatus(403);
    }

    public function test_admin_can_verify_a_pending_master_and_it_becomes_publicly_visible(): void
    {
        $admin = $this->admin();
        $master = MasterProfile::factory()->pending()->create();

        // not visible while pending
        $this->getJson("/api/masters/{$master->id}")->assertStatus(404);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/masters/{$master->id}/verification", ['verificationStatus' => 'verified'])
            ->assertOk()->assertJsonPath('data.verificationStatus', 'verified');

        $this->getJson("/api/masters/{$master->id}")->assertOk();
    }

    public function test_admin_can_manage_service_catalog(): void
    {
        $admin = $this->admin();

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/services', ['name' => 'Wheel alignment']);
        $create->assertCreated();
        $serviceId = $create->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/services/{$serviceId}", ['name' => 'Wheel alignment & balancing'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/admin/services/{$serviceId}")->assertOk();
        $this->assertDatabaseCount('services', 0);
    }

    public function test_service_in_use_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->create();
        $master = MasterProfile::factory()->create();
        MasterService::factory()->for($master, 'masterProfile')->for($service)->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/services/{$service->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_admin_can_list_all_bookings_across_workshops(): void
    {
        $admin = $this->admin();
        $masterA = MasterProfile::factory()->create();
        $masterB = MasterProfile::factory()->create();
        Booking::factory()->for($masterA, 'masterProfile')
            ->for(MasterService::factory()->for($masterA, 'masterProfile'), 'masterService')->create();
        Booking::factory()->for($masterB, 'masterProfile')
            ->for(MasterService::factory()->for($masterB, 'masterProfile'), 'masterService')->create();

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/bookings')
            ->assertOk()->assertJsonPath('data.meta.total', 2);
    }

    public function test_admin_can_toggle_review_visibility_and_rating_recalculates(): void
    {
        $admin = $this->admin();
        $master = MasterProfile::factory()->create(['rating' => 0, 'review_count' => 0]);
        $review = Review::factory()->for($master, 'masterProfile')->create(['rating' => 4, 'hidden' => false]);
        $master->recalculateRating();

        $this->assertDatabaseHas('master_profiles', ['id' => $master->id, 'review_count' => 1]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/hide")
            ->assertOk()->assertJsonPath('data.hidden', true);

        $this->assertDatabaseHas('master_profiles', ['id' => $master->id, 'review_count' => 0]);
    }

    public function test_admin_can_send_a_broadcast_notification_visible_to_target_audience(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $mechanic = User::factory()->mechanic()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/notifications/broadcast', [
            'title' => 'Platform maintenance',
            'body' => 'Scheduled downtime tonight.',
            'audience' => 'customers',
        ])->assertCreated();

        $this->actingAs($customer, 'sanctum')->getJson('/api/notifications')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($mechanic, 'sanctum')->getJson('/api/notifications')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_reports_return_expected_shape(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create();
        User::factory()->mechanic()->create();

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reports/overview')
            ->assertOk()
            ->assertJsonStructure(['data' => ['users', 'masters', 'bookings', 'reviews']]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reports/signups?days=7')
            ->assertOk()->assertJsonCount(7, 'data');

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reports/top-services')
            ->assertOk();
    }

    public function test_admin_master_listing_includes_services(): void
    {
        $admin = $this->admin();
        $master = MasterProfile::factory()->create();
        $service = Service::factory()->create(['name' => 'Oil change']);
        MasterService::factory()->for($master, 'masterProfile')->for($service)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/masters')->assertOk();

        $services = collect($response->json('data.items'))->firstWhere('id', $master->id)['services'];
        $this->assertEquals('Oil change', $services[0]['name']);
    }

    public function test_admin_can_override_booking_status(): void
    {
        $admin = $this->admin();
        $master = MasterProfile::factory()->create();
        $masterService = MasterService::factory()->for($master, 'masterProfile')->create();
        $booking = Booking::factory()->for($master, 'masterProfile')->for($masterService, 'masterService')
            ->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/bookings/{$booking->id}/status", ['status' => 'cancelled'])
            ->assertOk();

        $response->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('booking_status_logs', ['booking_id' => $booking->id, 'label' => 'Updated by admin']);
    }

    public function test_non_admin_cannot_override_booking_status(): void
    {
        $customer = User::factory()->create();
        $master = MasterProfile::factory()->create();
        $masterService = MasterService::factory()->for($master, 'masterProfile')->create();
        $booking = Booking::factory()->for($master, 'masterProfile')->for($masterService, 'masterService')->create();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/bookings/{$booking->id}/status", ['status' => 'cancelled'])
            ->assertStatus(403);
    }

    public function test_admin_review_listing_includes_master(): void
    {
        $admin = $this->admin();
        $master = MasterProfile::factory()->create(['workshop_name' => 'Chilanzar Servis']);
        Review::factory()->for($master, 'masterProfile')->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reviews')->assertOk();

        $this->assertEquals('Chilanzar Servis', $response->json('data.items.0.master.workshopName'));
    }

    public function test_admin_can_list_broadcast_history(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/notifications/broadcast', [
            'title' => 'Maintenance', 'body' => 'Downtime tonight.', 'audience' => 'all',
        ])->assertCreated();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/notifications')->assertOk();

        $this->assertEquals(1, $response->json('data.meta.total'));
        $this->assertEquals('Maintenance', $response->json('data.items.0.title'));
    }

    public function test_admin_seeder_creates_and_updates_admin_and_allows_login(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $admin = User::where('email', 'admin@ustago.uz')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('active', $admin->status);
        $this->assertNotNull($admin->email_verified_at);

        // Login via email
        $emailLogin = $this->postJson('/api/auth/login', [
            'identifier' => 'admin@ustago.uz',
            'password' => 'Admin12345!',
        ])->assertOk();
        $this->assertEquals('admin', $emailLogin->json('data.user.role'));

        // Consecutive run must be idempotent
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->assertEquals(1, User::where('role', 'admin')->count());

        // Login via phone
        $phoneLogin = $this->postJson('/api/auth/login', [
            'identifier' => '+998900000001',
            'password' => 'Admin12345!',
        ])->assertOk();
        $this->assertEquals('admin', $phoneLogin->json('data.user.role'));
    }
}

