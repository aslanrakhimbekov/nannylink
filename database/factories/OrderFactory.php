<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\OrderPaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'parent_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'address_string' => $this->faker->address(),
            'child_age' => 3,
            'date_start' => now()->addDays(1),
            'date_end' => now()->addDays(1)->addHours(4),
            'payment_type' => OrderPaymentType::HOURLY,
            'budget' => 2000,
            'status' => OrderStatus::OPEN,
            'latitude' => 43.238949,
            'longitude' => 76.889709,
        ];
    }
}
