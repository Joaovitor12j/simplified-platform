<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

final readonly class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @param  array{payer_wallet_id: string, payee_wallet_id: string, amount: string}  $data
     */
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }
}
