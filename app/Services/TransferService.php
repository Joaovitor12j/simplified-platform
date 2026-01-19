<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TransferDTO;
use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Serviço responsável pelo gerenciamento de transferências de dinheiro entre usuários.
 */
final readonly class TransferService implements TransferServiceInterface
{
    public function __construct(
        private WalletRepositoryInterface $wallets,
        private TransactionRepositoryInterface $transactions,
        private AuthorizationServiceInterface $authorizer,
        private DatabaseManager $databaseManager,
        private Dispatcher $dispatcher
    ) {}

    /**
     * Executes a transfer between two users.
     *
     * @throws Throwable
     */
    public function execute(TransferDTO $data): Transaction
    {
        $payerId = $data->payerId;
        $payeeId = $data->payeeId;
        $amount = $data->amount;

        $wallets = $this->wallets->findMany([$payerId, $payeeId]);
        $payerWallet = $wallets->firstWhere('user_id', $payerId);

        if (! $payerWallet) {
            throw new ModelNotFoundException('Pagador não encontrado.');
        }

        $payerWallet->user->validateCanTransfer();
        $this->authorizer->authorize();

        $transaction = $this->databaseManager->transaction(function () use ($payerId, $payeeId, $amount) {
            $wallets = $this->wallets->findManyForUpdate([$payerId, $payeeId]);

            $payerWallet = $wallets->firstWhere('user_id', $payerId);
            $payeeWallet = $wallets->firstWhere('user_id', $payeeId);

            if (! $payerWallet || ! $payeeWallet) {
                throw new ModelNotFoundException('Uma ou mais carteiras não encontradas.');
            }

            $payerWallet->validateBalance($amount);

            $this->wallets->updateBalance($payerWallet->id, '-'.$amount);
            $this->wallets->updateBalance($payeeWallet->id, $amount);

            return $this->transactions->create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $amount,
            ]);
        });

        $this->databaseManager->afterCommit(fn () => $this->dispatcher->dispatch(new TransactionCompleted($transaction, $payerId, $payeeId)));

        return $transaction;
    }
}
