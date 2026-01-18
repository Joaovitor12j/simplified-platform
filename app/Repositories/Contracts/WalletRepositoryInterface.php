<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Wallet;
use Illuminate\Support\Collection;

interface WalletRepositoryInterface
{
    public function updateBalance(string $walletId, string $amount): void;

    public function findByUserId(string $userId): ?Wallet;

    public function findByUserIdForUpdate(string $userId): ?Wallet;

    public function findMany(array $userIds): Collection;

    public function findManyForUpdate(array $userIds): Collection;
}
