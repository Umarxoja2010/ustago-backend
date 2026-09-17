<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'audience' => ['required', Rule::in(['all', 'customers', 'mechanics'])],
            'kind' => ['nullable', 'string', 'max:60'],
        ];
    }
}
