<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

class UnauthorizedTransactionException extends Exception
{
    public function __construct(string $message = "Transaчуo nуo autorizada pelo serviчo externo.")
    {
        parent::__construct($message, 403);
    }
}
