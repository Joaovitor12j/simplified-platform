<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Core\Domain\Repositories\TransactionRepositoryInterface;

final readonly class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        private Transaction $model
    ) {}

    /**
     * @param  array{payer_wallet_id: string, payee_wallet_id: string, amount: string}  $data
     */
    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }
}
