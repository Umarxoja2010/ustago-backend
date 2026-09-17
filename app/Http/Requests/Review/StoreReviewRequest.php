<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership + "completed" check happens in the controller
    }

    public function rules(): array
    {
        return [
            'bookingId' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
