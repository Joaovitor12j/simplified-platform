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

    /**
     * @param  array{payer: string, payee: string, value: string|float|int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            payerId: (string) $data['payer'],
            payeeId: (string) $data['payee'],
            amount: number_format((float) $data['value'], 2, '.', '')
        );
    }
}
