<?php

namespace Database\Factories;

use App\Models\Response;
use App\Models\Order;
use App\Models\User;
use App\Enums\ResponseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponseFactory extends Factory
{
    protected $model = Response::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'nanny_id' => User::factory(),
            'coin_cost' => 500,
            'status' => ResponseStatus::PENDING,
        ];
    }
}
