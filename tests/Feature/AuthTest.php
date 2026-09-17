<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'role' => 'customer',
            'name' => 'Aziz Karimov',
            'phone' => '+998901112233',
            'email' => 'aziz@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['phone' => '+998901112233', 'role' => 'customer']);
    }

    public function test_mechanic_registration_creates_master_profile(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'role' => 'mechanic',
            'name' => 'Shuhrat Nazarov',
            'phone' => '+998903334455',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'workshop' => [
                'name' => 'Avto Master Servis',
                'address' => 'Chilanzar 12',
                'city' => 'Tashkent',
                'lat' => 41.285512,
                'lng' => 69.203841,
                'experience' => 8,
                'services' => 'Engine repair, Oil change',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.workshop.name', 'Avto Master Servis')
            ->assertJsonPath('data.user.workshop.lat', 41.285512)
            ->assertJsonPath('data.user.workshop.lng', 69.203841);

        $this->assertDatabaseHas('master_profiles', [
            'workshop_name' => 'Avto Master Servis',
            'lat' => 41.285512,
            'lng' => 69.203841,
            'verification_status' => 'pending',
        ]);
    }

    public function test_registration_rejects_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'role' => 'admin',
            'name' => 'Someone',
            'phone' => '+998900000000',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_user_can_login_with_email_or_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'phone' => '+998905556677',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/auth/login', [
            'identifier' => $user->phone,
            'password' => 'secret123',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/auth/login', [
            'identifier' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_fetch_self_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', (string) $user->id);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_unauthenticated_request_to_protected_route_is_rejected(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $token = $user->createToken('auth')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/user', [
                'name' => 'Updated Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    public function test_authenticated_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/avatar', [
                'avatar' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $avatarUrl = $response->json('data.avatar');
        $this->assertNotEmpty($avatarUrl);
        $this->assertStringContainsString('/storage/avatars/', $avatarUrl);
    }
}
