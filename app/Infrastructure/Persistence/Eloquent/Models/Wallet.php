<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Exceptions\Domain\InsufficientBalanceException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $balance
 * @property \App\Infrastructure\Persistence\Eloquent\Models\User $user
 */
class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return \Database\Factories\WalletFactory::new();
    }

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions sent from this wallet.
     */
    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payer_wallet_id');
    }

    /**
     * Get the transactions received by this wallet.
     */
    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payee_wallet_id');
    }

    /**
     * Validates if the wallet has enough balance.
     *
     * @throws InsufficientBalanceException
     */
    public function validateBalance(string $amount): void
    {
        if (bccomp($this->balance, $amount, 2) === -1) {
            throw new InsufficientBalanceException;
        }
    }
}
