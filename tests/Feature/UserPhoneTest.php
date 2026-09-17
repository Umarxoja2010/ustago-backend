<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_phones(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/phones')
            ->assertOk();

        $response->assertJsonPath('data.0.phone', '+998901234567');
        $response->assertJsonPath('data.0.isPrimary', true);
    }

    public function test_user_can_add_up_to_3_phones(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);

        // Add 2nd phone
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/phones', ['phone' => '+998912345678'])
            ->assertStatus(201);

        // Add 3rd phone
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/phones', ['phone' => '+998933456789'])
            ->assertStatus(201);

        // Add 4th phone should fail
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/phones', ['phone' => '+998944567890'])
            ->assertStatus(422);

        $this->assertEquals(3, $user->phones()->count());
    }

    public function test_cannot_delete_primary_phone(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);
        $phone = UserPhone::create([
            'user_id' => $user->id,
            'phone' => '+998901234567',
            'is_primary' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/user/phones/{$phone->id}")
            ->assertStatus(422);
    }

    public function test_deleting_phone_schedules_deletion_for_14_days(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);
        $phone2 = UserPhone::create([
            'user_id' => $user->id,
            'phone' => '+998912345678',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/user/phones/{$phone2->id}")
            ->assertOk();

        $phone2->refresh();
        $this->assertNotNull($phone2->scheduled_deletion_at);
        $this->assertEquals(14, (int) round(now()->diffInDays($phone2->scheduled_deletion_at)));
    }

    public function test_can_restore_scheduled_phone(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);
        $phone2 = UserPhone::create([
            'user_id' => $user->id,
            'phone' => '+998912345678',
            'is_primary' => false,
            'scheduled_deletion_at' => now()->addDays(14),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/phones/{$phone2->id}/restore")
            ->assertOk();

        $phone2->refresh();
        $this->assertNull($phone2->scheduled_deletion_at);
    }

    public function test_can_switch_primary_phone(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567']);
        $phone1 = UserPhone::create([
            'user_id' => $user->id,
            'phone' => '+998901234567',
            'is_primary' => true,
        ]);
        $phone2 = UserPhone::create([
            'user_id' => $user->id,
            'phone' => '+998912345678',
            'is_primary' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/phones/{$phone2->id}/primary")
            ->assertOk();

        $phone1->refresh();
        $phone2->refresh();
        $user->refresh();

        $this->assertFalse($phone1->is_primary);
        $this->assertTrue($phone2->is_primary);
        $this->assertEquals('+998912345678', $user->phone);
    }
}
