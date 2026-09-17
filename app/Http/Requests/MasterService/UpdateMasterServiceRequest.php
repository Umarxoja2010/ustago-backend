<?php

namespace App\Http\Requests\MasterService;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership enforced in controller
    }

    public function rules(): array
    {
        return [
            'duration' => ['sometimes', 'nullable', 'string', 'max:60'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:2000000000'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
