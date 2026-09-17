<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by auth:sanctum middleware on the route
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($userId)],
            'region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'password' => ['sometimes', 'string', 'min:6', 'confirmed'],
        ];
    }
}
