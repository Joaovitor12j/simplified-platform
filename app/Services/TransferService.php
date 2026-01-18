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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Serviço responsável pelo gerenciamento de transferências de dinheiro entre usuários.
 */
final readonly class TransferService implements TransferServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private AuthorizationServiceInterface $authorizationService
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

        $users = $this->userRepository->findMany([$payerId, $payeeId]);

        $payer = $users->firstWhere('id', $payerId);
        if (! $payer) {
            throw new ModelNotFoundException('Usuário não encontrado.');
        }

        $this->validatePayerType($payer);
        $this->authorizeTransaction();

        $transaction = DB::transaction(function () use ($payerId, $payeeId, $value) {
            $wallets = $this->walletRepository->findWalletsByUserIds([$payerId, $payeeId], true);

            $payerWallet = $wallets->firstWhere('user_id', $payerId);
            $payeeWallet = $wallets->firstWhere('user_id', $payeeId);

            if (! $payerWallet || ! $payeeWallet) {
                throw new ModelNotFoundException('Uma ou mais carteiras não encontradas.');
            }

            $this->validateBalance($payerWallet, $value);

            $this->walletRepository->updateBalance($payerWallet->id, '-'.$value);
            $this->walletRepository->updateBalance($payeeWallet->id, $value);

            return $this->transactionRepository->create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $value,
            ]);
        });

        DB::afterCommit(fn () => SendNotificationJob::dispatch($transaction));

        Log::info('Transferência realizada com sucesso', [
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
        $this->authorizationService->authorize();
    }
}
