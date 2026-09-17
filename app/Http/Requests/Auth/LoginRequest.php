<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Accepts either email or phone, matching the frontend's single
            // "email or phone" login field.
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
