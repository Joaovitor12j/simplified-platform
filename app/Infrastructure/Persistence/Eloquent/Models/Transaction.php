<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return \Database\Factories\TransactionFactory::new();
    }

    protected $fillable = [
        'payer_wallet_id',
        'payee_wallet_id',
        'amount',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'amount' => 'decimal:2',
        'payer_wallet_id' => 'string',
        'payee_wallet_id' => 'string',
    ];

    /**
     * Get the wallet that sent the money.
     */
    public function payerWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'payer_wallet_id');
    }

    /**
     * Get the wallet that received the money.
     */
    public function payeeWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'payee_wallet_id');
    }
}
