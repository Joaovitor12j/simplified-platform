<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TransferDTO;
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
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Serviço responsável pelo gerenciamento de transferências de dinheiro entre usuários.
 */
final readonly class TransferService implements TransferServiceInterface
{
    public function __construct(
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
    public function execute(TransferDTO $transferDTO): Transaction
    {
        $payer = User::findOrFail($transferDTO->payerId);
        $payee = User::findOrFail($transferDTO->payeeId);

        $this->validatePayerType($payer);
        $this->authorizeTransaction();

        $transaction = DB::transaction(function () use ($payer, $payee, $transferDTO) {
            $payerWallet = $this->walletRepository->findByUserIdForUpdate((string) $payer->id);
            $payeeWallet = $this->walletRepository->findByUserIdForUpdate((string) $payee->id);

            $this->validateBalance($payerWallet, $transferDTO->amount);

            $this->walletRepository->updateBalance($payerWallet->id, '-'.$transferDTO->amount);
            $this->walletRepository->updateBalance($payeeWallet->id, $transferDTO->amount);

            return $this->transactionRepository->create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $transferDTO->amount,
            ]);
        });

        Log::info('Transferência realizada com sucesso', [
            'id' => $transaction->id,
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => $transferDTO->amount,
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
