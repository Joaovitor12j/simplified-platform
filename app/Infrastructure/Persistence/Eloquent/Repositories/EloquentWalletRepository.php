<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Core\Domain\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\Wallet;
use App\Core\Domain\Repositories\WalletRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function __construct(
        private Wallet $model
    ) {}

    public function debit(string $walletId, Money $amount): void
    {
        $wallet = $this->model->findOrFail($walletId);
        $currentBalance = new Money($wallet->balance);
        $newBalance = $currentBalance->subtract($amount);
        $wallet->update(['balance' => $newBalance->getAmount()]);
    }

    public function credit(string $walletId, Money $amount): void
    {
        $wallet = $this->model->findOrFail($walletId);
        $currentBalance = new Money($wallet->balance);
        $newBalance = $currentBalance->add($amount);
        $wallet->update(['balance' => $newBalance->getAmount()]);
    }

    public function findByUserId(string $userId): ?Wallet
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function findByUserIdForUpdate(string $userId): ?Wallet
    {
        return $this->model->where('user_id', $userId)->lockForUpdate()->first();
    }

    public function findMany(array $userIds): Collection
    {
        return $this->model->whereIn('user_id', $userIds)->get();
    }

    public function findManyForUpdate(array $userIds): Collection
    {
        return $this->model->whereIn('user_id', $userIds)->lockForUpdate()->get();
    }
}
