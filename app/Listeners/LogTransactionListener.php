<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionCompleted;
use Psr\Log\LoggerInterface;

class LogTransactionListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function handle(TransactionCompleted $event): void
    {
        $this->logger->info('Transferência realizada com sucesso', [
            'id' => $event->transaction->id,
            'payer' => $event->payerId,
            'payee' => $event->payeeId,
            'value' => $event->transaction->amount,
        ]);
    }
}
