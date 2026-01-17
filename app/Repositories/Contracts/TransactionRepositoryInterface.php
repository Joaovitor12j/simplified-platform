<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;
}
