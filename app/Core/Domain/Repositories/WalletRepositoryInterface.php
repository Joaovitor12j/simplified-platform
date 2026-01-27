<?php

declare(strict_types=1);

namespace App\Core\Domain\Repositories;

use App\Core\Domain\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\Wallet;
use Illuminate\Support\Collection;

interface WalletRepositoryInterface
{
    public function debit(string $walletId, Money $amount): void;

    public function credit(string $walletId, Money $amount): void;

    public function findByUserId(string $userId): ?Wallet;

    public function findByUserIdForUpdate(string $userId): ?Wallet;

    /**
     * @param array<int, string> $userIds
     * @return Collection<int, Wallet>
     */
    public function findMany(array $userIds): Collection;

    /**
     * @param array<int, string> $userIds
     * @return Collection<int, Wallet>
     */
    public function findManyForUpdate(array $userIds): Collection;
}
