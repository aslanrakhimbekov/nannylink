<?php

namespace Database\Factories;

use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\Order;
use App\Enums\CoinTransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CoinTransactionFactory extends Factory
{
    protected $model = CoinTransaction::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'type' => CoinTransactionType::DEPOSIT,
            'amount' => 1000,
        ];
    }
}
