<?php

namespace App\Http\Requests\MasterService;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreMasterServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawName = trim((string) ($this->input('name') ?? $this->input('serviceName') ?? ''));

        // If no serviceId was provided, but a custom name was entered:
        if (! $this->input('serviceId') && $rawName !== '') {
            $service = Service::where('name', $rawName)->first();

            if (! $service) {
                $baseSlug = Str::slug($rawName) ?: 'custom-service';
                $slug = $baseSlug;
                $counter = 1;
                while (Service::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $service = Service::create([
                    'name' => $rawName,
                    'slug' => $slug,
                ]);
            }

            $this->merge(['serviceId' => $service->id]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $masterProfile = \App\Models\MasterProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'workshop_name' => $user->name,
                'address' => $user->city ?? 'Toshkent',
                'city' => $user->city ?? 'Toshkent',
                'lat' => 41.311081,
                'lng' => 69.240562,
                'verification_status' => 'pending',
            ]
        );

        $masterProfileId = $masterProfile->id;

        return [
            'serviceId' => [
                'required',
                'integer',
                'exists:services,id',
                Rule::unique('master_services', 'service_id')->where('master_profile_id', $masterProfileId),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'serviceName' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:60'],
            'price' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'serviceId.required' => 'Xizmat nomini tanlang yoki kiriting',
            'serviceId.unique' => "Bu xizmat allaqachon sizning ro'yxatingizda mavjud",
            'serviceId.exists' => 'Tanlangan xizmat topilmadi',
            'price.min' => "Narx 0 dan kam bo'lishi mumkin emas",
            'price.integer' => "Narx butun son bo'lishi kerak",
        ];
    }
}
