<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\TransferDTO;
use App\Models\Transaction;

interface TransferServiceInterface
{
    /**
     * Executes a transfer between two users.
     */
    public function execute(TransferDTO $data): Transaction;
}
