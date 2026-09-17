<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // transition legality enforced by BookingPolicy in the controller
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['accepted', 'rejected', 'completed', 'cancelled'])],
        ];
    }
}
