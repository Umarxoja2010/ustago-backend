<?php

namespace Tests\Feature;

use App\Models\SpecialOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialOfferTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_public_can_fetch_active_offers_only(): void
    {
        SpecialOffer::factory()->create([
            'title' => 'Active Offer',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        SpecialOffer::factory()->create([
            'title' => 'Inactive Offer',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertEquals('Active Offer', $response->json('data.0.title'));
    }

    public function test_non_admin_cannot_manage_offers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/offers')
            ->assertStatus(403);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/offers', [
                'title' => 'Test',
                'image' => 'https://example.com/img.jpg',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_crud_and_toggle_offers(): void
    {
        $admin = $this->admin();

        // 1. Create
        $createRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/offers', [
                'title' => 'Yangi aksiya',
                'subtitle' => 'Barcha xizmatlarga 15% chegirma',
                'cta' => 'Batafsil',
                'image' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=1200&q=80',
                'link' => '/search',
                'badge' => '-15%',
                'accent' => 'from-emerald-600 to-teal-700',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Yangi aksiya');

        $offerId = $createRes->json('data.id');

        // 2. List
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // 3. Show
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/offers/{$offerId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Yangi aksiya');

        // 4. Update
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/offers/{$offerId}", [
                'title' => 'Yangilangan aksiya',
                'image' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=1200&q=80',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Yangilangan aksiya');

        // 5. Toggle status
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/offers/{$offerId}/toggle")
            ->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('special_offers', [
            'id' => $offerId,
            'is_active' => false,
        ]);

        // 6. Delete
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/offers/{$offerId}")
            ->assertOk();

        $this->assertDatabaseMissing('special_offers', [
            'id' => $offerId,
        ]);
    }
}
