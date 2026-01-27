<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private string $amount;

    public function __construct(string|float|int|Money $amount)
    {
        if ($amount instanceof Money) {
            $this->amount = $amount->getAmount();
            return;
        }

        $formattedAmount = number_format((float) $amount, 2, '.', '');

        if (bccomp($formattedAmount, '0.00', 2) === -1) {
            throw new InvalidArgumentException('O valor não pode ser negativo.');
        }

        $this->amount = $formattedAmount;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function add(string|float|int|Money $other): self
    {
        $otherMoney = new self($other);
        return new self(bcadd($this->amount, $otherMoney->getAmount(), 2));
    }

    public function subtract(string|float|int|Money $other): self
    {
        $otherMoney = new self($other);
        $result = bcsub($this->amount, $otherMoney->getAmount(), 2);

        if (bccomp($result, '0.00', 2) === -1) {
            throw new InvalidArgumentException('Saldo resultante não pode ser negativo.');
        }

        return new self($result);
    }

    public function isGreaterThanOrEqual(string|float|int|Money $other): bool
    {
        $otherMoney = new self($other);
        return bccomp($this->amount, $otherMoney->getAmount(), 2) >= 0;
    }

    public function __toString(): string
    {
        return $this->amount;
    }
}
