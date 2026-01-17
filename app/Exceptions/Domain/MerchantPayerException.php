<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

class MerchantPayerException extends Exception
{
    public function __construct(string $message = "Lojistas não podem realizar transferências.")
    {
        parent::__construct($message, 403);
    }
}
