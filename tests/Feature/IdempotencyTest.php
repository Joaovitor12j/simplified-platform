<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_cached_response_when_idempotency_key_is_provided(): void
    {
        Queue::fake();

        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            config('services.authorization.url') => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ]),
        ]);

        $payload = [
            'value' => 50.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        $headers = ['Idempotency-Key' => 'test-key'];

        // WHEN
        $response1 = $this->postJson('/api/transfer', $payload, $headers);
        $response2 = $this->postJson('/api/transfer', $payload, $headers);

        // THEN
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertEquals($response1->getContent(), $response2->getContent());

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payer->id,
            'balance' => 50.00,
        ]);
    }

    public function test_should_return_429_when_another_request_is_processing(): void
    {
        $idempotencyKey = 'concurrent-key';
        $lockKey = "idempotency:$idempotencyKey:lock";

        Cache::lock($lockKey, 10)->get();

        $payload = [
            'value' => 50.00,
            'payer' => 1,
            'payee' => 2,
        ];

        $response = $this->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/transfer', $payload);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Request conflict/processing']);
    }

    public function test_should_not_use_idempotency_when_header_is_missing(): void
    {
        Queue::fake();

        // GIVEN
        $payer = User::factory()->create(['type' => UserType::COMMON]);
        $payee = User::factory()->create(['type' => UserType::SHOPKEEPER]);

        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0.00]);

        Http::fake([
            config('services.authorization.url') => Http::response([
                'status' => 'success',
                'data' => ['authorization' => true],
            ]),
        ]);

        $payload = [
            'value' => 10.00,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ];

        // WHEN
        $response1 = $this->postJson('/api/transfer', $payload);
        $response2 = $this->postJson('/api/transfer', $payload);

        // THEN
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $payer->id,
            'balance' => 80.00,
        ]);
    }
}
