<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TransferDTO
{
    public function __construct(
        public string $payerId,
        public string $payeeId,
        public string $amount
    ) {}
}
