<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
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
}
