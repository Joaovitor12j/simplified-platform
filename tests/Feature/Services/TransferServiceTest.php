<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTOs\TransferDTO;
use App\Enums\UserType;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\MerchantPayerException;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferServiceInterface $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferService = $this->app->make(TransferServiceInterface::class);
    }

    public function test_should_transfer_money_between_users_successfully(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => [
                    'authorization' => true,
                ],
            ]),
            'https://util.devi.tools/api/v1/notify' => Http::response([], 200),
        ]);

        $dto = new TransferDTO((string) $payer->id, (string) $payee->id, '50.00');

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($payer, $payee) {
                return $message === 'Transferência realizada com sucesso' &&
                       $context['payer'] === $payer->id &&
                       $context['payee'] === $payee->id &&
                       $context['value'] === '50.00';
            });

        // WHEN
        $this->transferService->execute($dto);

        // THEN
        $this->assertDatabaseHas('wallets', [
            'user_id' => $payer->id,
            'balance' => 50.00,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payee->id,
            'balance' => 50.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 50.00,
        ]);
    }

    public function test_should_throw_exception_when_payer_is_shopkeeper(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::SHOPKEEPER]);
        $payee = User::factory()->create(['type' => UserType::COMMON]);

        $dto = new TransferDTO((string) $payer->id, (string) $payee->id, '50.00');

        $this->expectException(MerchantPayerException::class);

        // WHEN
        $this->transferService->execute($dto);
    }

    public function test_should_throw_exception_when_insufficient_balance(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 40.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => [
                    'authorization' => true,
                ],
            ]),
        ]);

        $dto = new TransferDTO((string) $payer->id, (string) $payee->id, '50.00');

        $this->expectException(InsufficientBalanceException::class);

        // WHEN
        $this->transferService->execute($dto);
    }

    public function test_should_throw_exception_when_not_authorized_externally(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'fail',
                'data' => [
                    'authorization' => false,
                ],
            ]),
        ]);

        $dto = new TransferDTO((string) $payer->id, (string) $payee->id, '50.00');

        $this->expectException(UnauthorizedTransactionException::class);

        // WHEN
        $this->transferService->execute($dto);
    }
}
