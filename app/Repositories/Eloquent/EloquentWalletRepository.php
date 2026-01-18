<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function updateBalance(string $walletId, string $amount): void
    {
        $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
        $newBalance = bcadd((string) $wallet->balance, $amount, 2);
        $wallet->update(['balance' => $newBalance]);
    }

    public function findByUserId(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function findByUserIdForUpdate(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->lockForUpdate()->first();
    }

    public function findWalletsByUserIds(array $userIds, bool $lock = false): Collection
    {
        $query = Wallet::whereIn('user_id', $userIds);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }
}
