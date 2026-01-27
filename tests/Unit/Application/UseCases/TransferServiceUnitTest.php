<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use App\Core\Application\DTOs\TransferDTO;
use App\Core\Application\UseCases\TransferService;
use App\Core\Domain\Repositories\AuthorizationServiceInterface;
use App\Core\Domain\Repositories\TransactionRepositoryInterface;
use App\Core\Domain\Repositories\WalletRepositoryInterface;
use App\Core\Domain\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\Wallet;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Throwable;

class TransferServiceUnitTest extends MockeryTestCase
{
    private $walletRepository;
    private $transactionRepository;
    private $authorizationService;
    private $databaseManager;
    private $dispatcher;
    private $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletRepository = Mockery::mock(WalletRepositoryInterface::class);
        $this->transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $this->authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
        $this->databaseManager = Mockery::mock(DatabaseManager::class);
        $this->dispatcher = Mockery::mock(Dispatcher::class);

        $this->transferService = new TransferService(
            $this->walletRepository,
            $this->transactionRepository,
            $this->authorizationService,
            $this->databaseManager,
            $this->dispatcher
        );
    }

    public function test_should_execute_transfer_successfully(): void
    {
        // GIVEN
        $payerId = 'payer-uuid';
        $payeeId = 'payee-uuid';
        $amount = '100.00';
        $dto = new TransferDTO($payerId, $payeeId, $amount);

        $payerUser = Mockery::mock(User::class);
        $payerUser->shouldReceive('validateCanTransfer')->once();

        $payerWallet = Mockery::mock(Wallet::class)->makePartial();
        $payerWallet->id = 'wallet-payer-uuid';
        $payerWallet->user_id = $payerId;
        $payerWallet->user = $payerUser;
        $payerWallet->shouldReceive('validateBalance')->with($amount)->once();

        $payeeWallet = Mockery::mock(Wallet::class)->makePartial();
        $payeeWallet->id = 'wallet-payee-uuid';
        $payeeWallet->user_id = $payeeId;

        $wallets = new Collection([$payerWallet, $payeeWallet]);

        $this->databaseManager->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $this->walletRepository->shouldReceive('findManyForUpdate')
            ->with([$payerId, $payeeId])
            ->once()
            ->andReturn($wallets);

        $this->authorizationService->shouldReceive('authorize')->once();

        $this->walletRepository->shouldReceive('debit')
            ->with($payerWallet->id, Mockery::type(Money::class))
            ->once();

        $this->walletRepository->shouldReceive('credit')
            ->with($payeeWallet->id, Mockery::type(Money::class))
            ->once();

        $transaction = new Transaction();
        $this->transactionRepository->shouldReceive('create')
            ->once()
            ->andReturn($transaction);

        $this->databaseManager->shouldReceive('afterCommit')->once();

        // WHEN
        $result = $this->transferService->execute($dto);

        // THEN
        $this->assertSame($transaction, $result);
    }

    public function test_should_record_failed_transaction_on_exception(): void
    {
        // GIVEN
        $payerId = 'payer-uuid';
        $payeeId = 'payee-uuid';
        $amount = '100.00';
        $dto = new TransferDTO($payerId, $payeeId, $amount);

        $this->databaseManager->shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Generic error'));

        // recordFailedTransaction expectations
        $this->walletRepository->shouldReceive('findMany')
            ->with([$payerId, $payeeId])
            ->once()
            ->andReturn(new Collection());

        $this->transactionRepository->shouldReceive('create')
            ->with(Mockery::on(function ($data) {
                return $data['status'] === 'failed' && $data['failure_reason'] === 'Generic error';
            }))
            ->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Generic error');

        // WHEN
        $this->transferService->execute($dto);
    }
}
