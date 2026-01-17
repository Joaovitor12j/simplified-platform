<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)
            ->common()
            ->has(Wallet::factory()->state(['balance' => 100.00]))
            ->create();

        User::factory(5)
            ->shopkeeper()
            ->has(Wallet::factory()->state(['balance' => 0.00]))
            ->create();
    }
}
