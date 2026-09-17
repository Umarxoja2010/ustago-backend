<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // registration is public; admin role is excluded below
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $raw = (string) $this->phone;
            $digits = preg_replace('/\D/', '', $raw);
            if (str_starts_with($digits, '998998') && strlen($digits) >= 15) {
                $digits = substr($digits, 6);
            } elseif (str_starts_with($digits, '998')) {
                $digits = substr($digits, 3);
            }
            if (strlen($digits) === 9) {
                $this->merge(['phone' => '+998'.$digits]);
            }
        }

        if ($this->has('email')) {
            $email = trim((string) $this->email);
            $this->merge(['email' => empty($email) ? null : strtolower($email)]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->name)]);
        }
    }

    public function rules(): array
    {
        return [
            // Admin accounts are never created through registration (per spec).
            'role' => ['required', 'in:customer,mechanic'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^\+998\d{9}$/',
                function ($attribute, $value, $fail) {
                    $digits = preg_replace('/\D/', '', $value);
                    $last9 = substr($digits, -9);
                    $exists = \App\Models\User::query()
                        ->where('phone', $value)
                        ->orWhere('phone', 'like', '%' . $last9)
                        ->orWhereHas('phones', function ($q) use ($value, $last9) {
                            $q->where('phone', $value)->orWhere('phone', 'like', '%' . $last9);
                        })
                        ->exists();
                    if ($exists) {
                        $fail('errors.accountExists');
                    }
                },
            ],
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
            'phone.regex' => 'errors.invalidPhone',
            'phone.unique' => 'errors.accountExists',
            'email.unique' => 'errors.accountExists',
            'password.confirmed' => 'errors.passwordMismatch',
        ];
    }
}
