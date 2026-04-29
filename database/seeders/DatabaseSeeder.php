<?php

namespace Database\Seeders;

use App\Models\CentralSuperadmin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        CentralSuperadmin::query()->updateOrCreate(
            ['email' => env('CENTRAL_SUPERADMIN_EMAIL', 'superadmin@localhost')],
            [
                'name' => env('CENTRAL_SUPERADMIN_NAME', 'University Practicum Admin'),
                'password' => env('CENTRAL_SUPERADMIN_PASSWORD', 'password123'),
            ]
        );

        $this->call(CollegeSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password123',
            ]
        );
    }
}
