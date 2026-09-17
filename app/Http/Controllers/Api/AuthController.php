<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\MasterProfile;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => !empty($data['email']) ? strtolower($data['email']) : null,
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => $data['role'],
                'status' => 'active',
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
            ]);

            if ($data['role'] === 'mechanic') {
                $workshop = $data['workshop'] ?? [];

                MasterProfile::create([
                    'user_id' => $user->id,
                    'workshop_name' => $workshop['name'] ?? $data['name'],
                    'owner_name' => $data['name'],
                    'address' => $workshop['address'] ?? ($data['region'] ?? ''),
                    'city' => $data['city'] ?? ($workshop['city'] ?? 'Tashkent'),
                    'district' => $workshop['district'] ?? null,
                    'lat' => $workshop['lat'] ?? null,
                    'lng' => $workshop['lng'] ?? null,
                    'about' => $workshop['services'] ?? null,
                    'experience_years' => $workshop['experience'] ?? 0,
                    'verification_status' => app()->environment('local') ? 'verified' : 'pending',
                    'is_open' => true,
                ]);
            }

            $user->phones()->create([
                'phone' => $data['phone'],
                'is_primary' => true,
            ]);

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->ok([
            'user' => new UserResource($user->load(['masterProfile.masterServices.service', 'phones'])),
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = trim($request->string('identifier'));
        $isEmail = str_contains($identifier, '@');

        $user = User::query()
            ->when($isEmail, fn ($q) => $q->where('email', strtolower($identifier)))
            ->when(! $isEmail, function ($q) use ($identifier) {
                $cleaned = preg_replace('/[^\d+]/', '', $identifier);
                $digits = preg_replace('/\D/', '', $identifier);
                $q->where('phone', $identifier)
                    ->orWhere('phone', $cleaned)
                    ->orWhere('phone', '+' . $digits)
                    ->when(strlen($digits) >= 9, fn ($sq) => $sq->orWhere('phone', 'like', '%' . substr($digits, -9)));
            })
            ->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return $this->fail('errors.invalidCredentials', [], 401);
        }

        if ($user->status === 'suspended') {
            return $this->fail('errors.accountSuspended', [], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->ok([
            'user' => new UserResource($user->load(['masterProfile.masterServices.service', 'phones'])),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['masterProfile.masterServices.service', 'phones']);

        return $this->ok(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('email', $data)) {
            $data['email'] = !empty($data['email']) ? strtolower($data['email']) : null;
        }

        $user->update($data);

        return $this->ok(new UserResource($user->fresh(['masterProfile.masterServices.service', 'phones'])), 'Profile updated');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $url = asset('storage/' . $path);

        $user->update(['avatar' => $url]);

        if ($user->masterProfile) {
            $user->masterProfile->update(['logo' => $url]);
        }

        return $this->ok(new UserResource($user->fresh(['masterProfile.masterServices.service', 'phones'])), 'Avatar updated successfully');
    }
}
