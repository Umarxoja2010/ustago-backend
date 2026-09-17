<?php

namespace App\Http\Requests\WorkingHour;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.weekday' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.open' => ['nullable', 'date_format:H:i', 'required_if:hours.*.closed,false'],
            'hours.*.close' => ['nullable', 'date_format:H:i', 'required_if:hours.*.closed,false'],
            'hours.*.closed' => ['required', 'boolean'],
        ];
    }
}
