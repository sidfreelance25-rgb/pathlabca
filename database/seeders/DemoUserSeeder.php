<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ],
        );

        Doctor::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Dr. Sharma',
            ],
            [
                'phone' => '9876543210',
                'specialization' => 'General Physician',
                'is_active' => true,
            ],
        );
    }
}
