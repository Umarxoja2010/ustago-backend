<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Admin accounts are never created through public registration —
     * this is the only way one gets created (per spec section 11).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ustago.local')],
            [
                'name' => 'Platform Admin',
                'phone' => env('ADMIN_PHONE', '+998900000001'),
                'password' => env('ADMIN_PASSWORD', 'change-me-now'),
                'role' => 'admin',
                'status' => 'active',
            ],
        );
    }
}
