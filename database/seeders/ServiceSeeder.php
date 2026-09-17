<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Engine repair', 'Oil change', 'Brake repair', 'Diagnostics',
            'Tire fitting', 'AC service', 'Battery replacement',
            'Suspension repair', 'Transmission service', 'Body work',
            'Other services',
        ];

        foreach ($catalog as $name) {
            Service::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
