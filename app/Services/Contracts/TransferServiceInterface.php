<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Transaction;
use App\Models\User;

interface TransferServiceInterface
{
    /**
     * Executes a transfer between two users.
     *
     * @param User $payer
     * @param User $payee
     * @param float $value
     * @return Transaction
     */
    public function execute(User $payer, User $payee, float $value): Transaction;
}
