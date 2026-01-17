<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface AuthorizationServiceInterface
{
    public function authorize(): bool;
}
