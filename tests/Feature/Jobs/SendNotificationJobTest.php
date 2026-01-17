<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_send_notification_successfully(): void
    {
        // GIVEN
        Http::fake([
            'https://util.devi.tools/api/v1/notify' => Http::response([]),
        ]);

        $payer = User::factory()->create();
        $payee = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $payer->id]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payee->id]);

        $transaction = Transaction::create([
            'payer_wallet_id' => $payerWallet->id,
            'payee_wallet_id' => $payeeWallet->id,
            'amount' => '100.00',
        ]);

        $job = new SendNotificationJob($transaction);

        // WHEN
        $job->handle();

        // THEN
        Http::assertSent(function ($request) {
            return $request->url() === 'https://util.devi.tools/api/v1/notify' &&
                   $request->method() === 'POST';
        });
    }

    public function test_should_throw_exception_when_notification_fails(): void
    {
        // GIVEN
        Http::fake([
            'https://util.devi.tools/api/v1/notify' => Http::response([], 500),
        ]);

        $payer = User::factory()->create();
        $payee = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $payer->id]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payee->id]);

        $transaction = Transaction::create([
            'payer_wallet_id' => $payerWallet->id,
            'payee_wallet_id' => $payeeWallet->id,
            'amount' => '100.00',
        ]);

        $job = new SendNotificationJob($transaction);

        // EXPECT
        $this->expectException(RequestException::class);

        // WHEN
        $job->handle();
    }

    public function test_should_log_error_when_job_fails(): void
    {
        // GIVEN
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Notification failed for transaction');
            });

        $payer = User::factory()->create();
        $payee = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $payer->id]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payee->id]);

        $transaction = Transaction::create([
            'payer_wallet_id' => $payerWallet->id,
            'payee_wallet_id' => $payeeWallet->id,
            'amount' => '100.00',
        ]);

        $job = new SendNotificationJob($transaction);

        // WHEN
        $job->failed(new Exception('Test error'));
    }
}
