<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SpecialOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta' => ['nullable', 'string', 'max:60'],
            'image' => ['required', 'string', 'max:1000'],
            'link' => ['nullable', 'string', 'max:500'],
            'badge' => ['nullable', 'string', 'max:60'],
            'accent' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
