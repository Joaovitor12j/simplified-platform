<?php

declare(strict_types=1);

namespace App\Core\Application\UseCases;

use App\Core\Application\DTOs\TransferDTO;
use App\Core\Domain\ValueObjects\Money;
use App\Events\TransactionCompleted;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Core\Domain\Repositories\TransactionRepositoryInterface;
use App\Core\Domain\Repositories\WalletRepositoryInterface;
use App\Core\Domain\Repositories\AuthorizationServiceInterface;
use App\Core\Application\UseCases\TransferServiceInterface;
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
        $money = new Money($data->amount);

        try {
            $transaction = $this->databaseManager->transaction(function () use ($payerId, $payeeId, $money) {
                /** @var \Illuminate\Support\Collection<int, \App\Infrastructure\Persistence\Eloquent\Models\Wallet> $wallets */
                $wallets = $this->wallets->findManyForUpdate([$payerId, $payeeId]);

                /** @var \App\Infrastructure\Persistence\Eloquent\Models\Wallet|null $payerWallet */
                $payerWallet = $wallets->firstWhere('user_id', $payerId);
                /** @var \App\Infrastructure\Persistence\Eloquent\Models\Wallet|null $payeeWallet */
                $payeeWallet = $wallets->firstWhere('user_id', $payeeId);

                if (! $payerWallet || ! $payeeWallet) {
                    throw new ModelNotFoundException('Uma ou mais carteiras não encontradas.');
                }

                $payerWallet->user->validateCanTransfer();
                $payerWallet->validateBalance($money->getAmount());
                
                $this->authorizer->authorize();

                $this->wallets->debit($payerWallet->id, $money);
                $this->wallets->credit($payeeWallet->id, $money);

                return $this->transactions->create([
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'amount' => $money->getAmount(),
                    'status' => 'completed',
                ]);
            });

            $this->databaseManager->afterCommit(fn () => $this->dispatcher->dispatch(
                new TransactionCompleted($transaction, $payerId, $payeeId)
            ));

            return $transaction;
        } catch (Throwable $e) {
            $this->recordFailedTransaction($payerId, $payeeId, $money, $e->getMessage());
            throw $e;
        }
    }

    private function recordFailedTransaction(string $payerId, string $payeeId, Money $amount, string $reason): void
    {
        try {
            $wallets = $this->wallets->findMany([$payerId, $payeeId]);
            $payerWallet = $wallets->firstWhere('user_id', $payerId);
            $payeeWallet = $wallets->firstWhere('user_id', $payeeId);

            $this->transactions->create([
                'payer_wallet_id' => $payerWallet?->id,
                'payee_wallet_id' => $payeeWallet?->id,
                'amount' => $amount->getAmount(),
                'status' => 'failed',
                'failure_reason' => $reason,
            ]);
        } catch (Throwable) {
            // Silently fail to avoid masking the original exception
        }
    }
}
