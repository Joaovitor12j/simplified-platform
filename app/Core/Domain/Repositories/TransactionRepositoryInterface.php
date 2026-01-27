<?php

declare(strict_types=1);

namespace App\Core\Domain\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * @param  array{payer_wallet_id: string, payee_wallet_id: string, amount: string}  $data
     */
    public function create(array $data): Transaction;
}
