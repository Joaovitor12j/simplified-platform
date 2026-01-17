<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\MerchantPayerException;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Serviço responsável pelo gerenciamento de transferências de dinheiro entre usuários.
 */
readonly final class TransferService implements TransferServiceInterface
{
    public function __construct(
        private WalletRepositoryInterface      $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private AuthorizationServiceInterface  $authorizationService
    ) {
    }

    /**
     * Executes a transfer between two users.
     *
     * @param User $payer
     * @param User $payee
     * @param string $value
     * @return Transaction
     *
     * @throws MerchantPayerException
     * @throws InsufficientBalanceException
     * @throws UnauthorizedTransactionException|Throwable
     */
    public function execute(User $payer, User $payee, string $value): Transaction
    {
        $this->validatePayerType($payer);
        $this->authorizeTransaction();

        return DB::transaction(function () use ($payer, $payee, $value) {
            $payerWallet = $this->walletRepository->findByUserIdForUpdate($payer->id);
            $payeeWallet = $this->walletRepository->findByUserIdForUpdate($payee->id);

            $this->validateBalance($payerWallet, $value);

            $this->walletRepository->updateBalance($payerWallet->id, '-' . $value);
            $this->walletRepository->updateBalance($payeeWallet->id, $value);

            return $this->transactionRepository->create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $value,
            ]);
        });
    }

    private function validatePayerType(User $payer): void
    {
        if ($payer->type->isShopkeeper()) {
            throw new MerchantPayerException();
        }
    }

    private function validateBalance(Wallet $wallet, string $value): void
    {
        if (bccomp($wallet->balance, $value, 2) === -1) {
            throw new InsufficientBalanceException();
        }
    }

    private function authorizeTransaction(): void
    {
        $this->authorizationService->authorize();
    }
}
