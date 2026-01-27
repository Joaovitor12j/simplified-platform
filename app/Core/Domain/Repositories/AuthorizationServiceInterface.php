<?php

declare(strict_types=1);

namespace App\Core\Domain\Repositories;

interface AuthorizationServiceInterface
{
    public function authorize(): bool;
}
