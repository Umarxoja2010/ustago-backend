<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the owning mechanic may edit their own profile — enforced via
        // the `mechanic` role middleware + scoping to $request->user() in
        // the controller (no route-model-binding on a foreign profile id).
        return true;
    }

    public function rules(): array
    {
        return [
            'workshopName' => ['sometimes', 'string', 'max:255'],
            'about' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'cover' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'address' => ['sometimes', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'city' => ['sometimes', 'string', 'max:120'],
            'lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'experienceYears' => ['sometimes', 'integer', 'min:0', 'max:80'],
            'isOpen' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Map camelCase API fields to the model's snake_case columns.
     */
    public function mapped(): array
    {
        $map = [
            'workshopName' => 'workshop_name',
            'experienceYears' => 'experience_years',
            'isOpen' => 'is_open',
        ];

        $data = $this->validated();
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];
                unset($data[$from]);
            }
        }

        return $data;
    }
}
