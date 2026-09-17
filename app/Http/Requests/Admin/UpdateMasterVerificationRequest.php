<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verificationStatus' => ['required', Rule::in(['pending', 'verified', 'rejected', 'suspended'])],
        ];
    }
}
