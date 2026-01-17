<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payer_wallet_id' => Wallet::factory(),
            'payee_wallet_id' => Wallet::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
