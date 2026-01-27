<?php

declare(strict_types=1);

namespace App\Core\Application\UseCases;

use App\Core\Application\DTOs\TransferDTO;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;

interface TransferServiceInterface
{
    /**
     * Executes a transfer between two users.
     */
    public function execute(TransferDTO $data): Transaction;
}
