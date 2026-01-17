<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * @param array{payer_wallet_id: string, payee_wallet_id: string, amount: string} $data
     * @return Transaction
     */
    public function create(array $data): Transaction;
}
