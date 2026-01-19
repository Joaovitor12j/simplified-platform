<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $payerId,
        public string $payeeId
    ) {}
}
