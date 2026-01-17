<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_execute_transfer_successfully_via_api(): void
    {
        Queue::fake();

        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ], 200),
        ]);

        $payload = [
            'value' => 50.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        // WHEN
        $response = $this->postJson('/api/transfer', $payload);

        // THEN
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'payer_wallet_id',
            'payee_wallet_id',
            'amount',
            'created_at',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payer->id,
            'balance' => 50.00,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payee->id,
            'balance' => 50.00,
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_should_fail_validation_when_data_is_missing(): void
    {
        $response = $this->postJson('/api/transfer', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['value', 'payer', 'payee']);
    }

    public function test_should_fail_when_payer_is_shopkeeper(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::SHOPKEEPER]);
        $payee = User::factory()->create(['type' => UserType::COMMON]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        $payload = [
            'value' => 50.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        // WHEN
        $response = $this->postJson('/api/transfer', $payload);

        // THEN
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Lojistas não podem realizar transferências.',
        ]);
    }

    public function test_should_fail_when_insufficient_balance(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 10.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ], 200),
        ]);

        $payload = [
            'value' => 50.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        // WHEN
        $response = $this->postJson('/api/transfer', $payload);

        // THEN
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Saldo insuficiente para realizar a transferência.',
        ]);
    }

    public function test_should_fail_when_external_authorization_service_fails(): void
    {
        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response([], 500),
        ]);

        $payload = [
            'value' => 50.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        // WHEN
        $response = $this->postJson('/api/transfer', $payload);

        // THEN
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Serviço de autorização externo indisponível.',
        ]);
    }
}
