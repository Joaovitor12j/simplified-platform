<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = "Saldo insuficiente para realizar a transferência.")
    {
        parent::__construct($message, 400);
    }
}
