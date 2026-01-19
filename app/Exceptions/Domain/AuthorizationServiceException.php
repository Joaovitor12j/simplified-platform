<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

class AuthorizationServiceException extends Exception
{
    public function __construct(string $message = 'Serviзo de autorizaзгo externo indisponнvel.')
    {
        parent::__construct($message, 502);
    }
}
