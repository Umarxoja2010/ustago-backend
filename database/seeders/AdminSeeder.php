<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Admin accounts are never created through public registration —
     * this seeder creates or updates the system administrator account.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@ustago.uz');
        $phone = env('ADMIN_PHONE', '+998900000001');
        $plainPassword = env('ADMIN_PASSWORD', 'Admin12345');

        // If an admin account exists under the old default email (admin@ustago.local),
        // migrate its email to the standard admin email to avoid phone collision.
        if ($email !== 'admin@ustago.local') {
            User::where('email', 'admin@ustago.local')->update(['email' => $email]);
        }

        // Idempotent creation or update of the admin user
        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Platform Admin',
                'phone' => $phone,
                'password' => Hash::make($plainPassword),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->phones()->updateOrCreate(
            ['phone' => $phone],
            ['is_primary' => true]
        );

        // Ensure baseline catalog services exist if running on a blank database (e.g. Render SQLite)
        if (Service::count() === 0) {
            $this->call(ServiceSeeder::class);
        }
    }
}

