<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\UserType;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\MerchantPayerException;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
                    'authorization' => true
                ]
            ], 200)
        ]);

        // WHEN
        $this->transferService->execute($payer, $payee, 50.00);

        // THEN
        $this->assertDatabaseHas('wallets', [
            'user_id' => $payer->id,
            'balance' => 50.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payee->id,
            'balance' => 50.00
        ]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 50.00
        ]);
    }

    public function test_should_throw_exception_when_payer_is_shopkeeper(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::SHOPKEEPER]);
        $payee = User::factory()->create(['type' => UserType::COMMON]);

        $this->expectException(MerchantPayerException::class);

        // WHEN
        $this->transferService->execute($payer, $payee, 50.00);
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
                    'authorization' => true
                ]
            ], 200)
        ]);

        $this->expectException(InsufficientBalanceException::class);

        // WHEN
        $this->transferService->execute($payer, $payee, 50.00);
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
                    'authorization' => false
                ]
            ], 200)
        ]);

        $this->expectException(UnauthorizedTransactionException::class);

        // WHEN
        $this->transferService->execute($payer, $payee, 50.00);
    }
}
