<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'patient_name' => $this->faker->name(),
            'referred_by_doctor_id' => null,
            'referred_by_name' => Report::REFERRED_BY_SELF,
            'status' => 'draft',
        ];
    }
}
