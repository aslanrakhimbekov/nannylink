<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'hourly_rate' => 1500,
            'experience_years' => 3,
            'balance_coins' => 1000,
            'is_verified' => false,
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ];
    }
}
