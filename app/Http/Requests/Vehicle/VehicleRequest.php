<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // apiResource maps both PUT and PATCH to update() — treat either as
        // a partial update. Only the store (POST) route requires full fields.
        $sometimesOnUpdate = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'brand' => [$sometimesOnUpdate, 'string', 'max:120'],
            'model' => [$sometimesOnUpdate, 'string', 'max:120'],
            'year' => ['nullable', 'digits:4'],
            'engine' => ['nullable', 'string', 'max:60'],
            'plate' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:40'],
        ];
    }
}
