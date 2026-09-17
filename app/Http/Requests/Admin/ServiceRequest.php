<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimesOnUpdate = $this->isMethod('POST') ? 'required' : 'sometimes';
        $serviceId = $this->route('service')?->id;

        return [
            'name' => [$sometimesOnUpdate, 'string', 'max:255', Rule::unique('services', 'name')->ignore($serviceId)],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
