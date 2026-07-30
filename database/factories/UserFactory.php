<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'phone' => '+7701' . $this->faker->unique()->numerify('#######'),
            'telegram_id' => $this->faker->unique()->word(),
            'role' => UserRole::PARENT,
            'status' => UserStatus::ACTIVE,
            'language' => UserLanguage::RU,
        ];
    }
}
