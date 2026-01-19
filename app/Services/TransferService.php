<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TransferDTO;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\MerchantPayerException;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serviço responsável pelo gerenciamento de transferências de dinheiro entre usuários.
 */
final readonly class TransferService implements TransferServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private WalletRepositoryInterface $wallets,
        private TransactionRepositoryInterface $transactions,
        private AuthorizationServiceInterface $authorizer,
        private DatabaseManager $databaseManager,
        private LoggerInterface $logger,
        private Dispatcher $dispatcher
    ) {}

    /**
     * Executes a transfer between two users.
     *
     * @throws MerchantPayerException
     * @throws InsufficientBalanceException
     * @throws UnauthorizedTransactionException|Throwable
     */
    public function execute(TransferDTO $data): Transaction
    {
        $payerId = $data->payerId;
        $payeeId = $data->payeeId;
        $value = $data->amount;

        $users = $this->users->findMany([$payerId, $payeeId]);

        $payer = $users->firstWhere('id', $payerId);
        if (! $payer) {
            throw new ModelNotFoundException('Usuário não encontrado.');
        }

        $this->validatePayerType($payer);
        $this->authorizeTransaction();

        $transaction = $this->databaseManager->transaction(function () use ($payerId, $payeeId, $value) {
            $wallets = $this->wallets->findManyForUpdate([$payerId, $payeeId]);

            $payerWallet = $wallets->firstWhere('user_id', $payerId);
            $payeeWallet = $wallets->firstWhere('user_id', $payeeId);

            if (! $payerWallet || ! $payeeWallet) {
                throw new ModelNotFoundException('Uma ou mais carteiras não encontradas.');
            }

            $this->validateBalance($payerWallet, $value);

            $this->wallets->updateBalance($payerWallet->id, '-'.$value);
            $this->wallets->updateBalance($payeeWallet->id, $value);

            return $this->transactions->create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $value,
            ]);
        });

        $this->databaseManager->afterCommit(fn () => $this->dispatcher->dispatch(new SendNotificationJob($transaction)));

        $this->logger->info('Transferência realizada com sucesso', [
            'id' => $transaction->id,
            'payer' => $payerId,
            'payee' => $payeeId,
            'value' => $value,
        ]);

        return $transaction;
    }

    private function validatePayerType(User $payer): void
    {
        if ($payer->type->isShopkeeper()) {
            throw new MerchantPayerException;
        }
    }

    private function validateBalance(Wallet $wallet, string $value): void
    {
        if (bccomp($wallet->balance, $value, 2) === -1) {
            throw new InsufficientBalanceException;
        }
    }

    private function authorizeTransaction(): void
    {
        $this->authorizer->authorize();
    }
}
