<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'], // name search
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'service' => ['sometimes', 'nullable', 'string', 'max:255'], // service name or id
            'minRating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'maxPrice' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maxDistance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'sort' => ['sometimes', 'nullable', Rule::in(['rating', 'name', 'nearest', 'distance', 'price'])],
            'perPage' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
