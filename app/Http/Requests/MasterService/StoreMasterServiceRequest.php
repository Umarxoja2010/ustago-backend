<?php

namespace App\Http\Requests\MasterService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMasterServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $masterProfileId = $this->user()->masterProfile?->id;

        return [
            'serviceId' => [
                'required',
                'integer',
                'exists:services,id',
                Rule::unique('master_services', 'service_id')->where('master_profile_id', $masterProfileId),
            ],
            'duration' => ['nullable', 'string', 'max:60'],
            'price' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
