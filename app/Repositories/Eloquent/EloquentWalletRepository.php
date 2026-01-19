<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function __construct(
        private Wallet $model
    ) {}

    public function updateBalance(string $walletId, string $amount): void
    {
        $wallet = $this->model->lockForUpdate()->findOrFail($walletId);
        $newBalance = bcadd((string) $wallet->balance, $amount, 2);
        $wallet->update(['balance' => $newBalance]);
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
