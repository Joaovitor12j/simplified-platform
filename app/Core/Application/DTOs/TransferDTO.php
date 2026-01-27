<?php

declare(strict_types=1);

namespace App\Core\Application\DTOs;

final readonly class TransferDTO
{
    public function __construct(
        public string $payerId,
        public string $payeeId,
        public string $amount
    ) {}
}
