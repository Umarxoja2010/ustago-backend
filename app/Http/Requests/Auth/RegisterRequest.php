<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // registration is public; admin role is excluded below
    }

    public function rules(): array
    {
        return [
            // Admin accounts are never created through registration (per spec).
            'role' => ['required', 'in:customer,mechanic'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            // Viloyat va shahar
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],

            // Usta ustaxonasi ma'lumotlari
            'workshop' => ['required_if:role,mechanic', 'array'],
            'workshop.name' => ['required_if:role,mechanic', 'string', 'max:255'],
            'workshop.address' => ['required_if:role,mechanic', 'string', 'max:255'],
            'workshop.city' => ['nullable', 'string', 'max:120'],
            'workshop.district' => ['nullable', 'string', 'max:120'],
            'workshop.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'workshop.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'workshop.experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'workshop.services' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'errors.accountExists',
            'email.unique' => 'errors.accountExists',
        ];
    }
}
