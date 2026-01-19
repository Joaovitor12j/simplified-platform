<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_send_notification_successfully(): void
    {
        // GIVEN
        Http::fake([
            config('services.notification.url') => Http::response([]),
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

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Notificação enviada com sucesso', [
                'transaction_id' => $transaction->id,
            ]);

        $job = new SendNotificationJob($transaction);

        // WHEN
        $job->handle($logger, $this->app->make(HttpFactory::class));

        // THEN
        Http::assertSent(function ($request) {
            return $request->url() === config('services.notification.url') &&
                   $request->method() === 'POST';
        });
    }

    public function test_should_throw_exception_when_notification_fails(): void
    {
        // GIVEN
        Http::fake([
            config('services.notification.url') => Http::response([], 500),
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

        $logger = $this->createMock(LoggerInterface::class);
        $job = new SendNotificationJob($transaction);

        // EXPECT
        $this->expectException(RequestException::class);

        // WHEN
        $job->handle($logger, $this->app->make(HttpFactory::class));
    }

    public function test_should_log_error_when_job_fails(): void
    {
        // GIVEN
        $logger = $this->createMock(LoggerInterface::class);
        $this->app->instance(LoggerInterface::class, $logger);

        $payer = User::factory()->create();
        $payee = User::factory()->create();
        $payerWallet = Wallet::factory()->create(['user_id' => $payer->id]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payee->id]);

        $transaction = Transaction::create([
            'payer_wallet_id' => $payerWallet->id,
            'payee_wallet_id' => $payeeWallet->id,
            'amount' => '100.00',
        ]);

        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Notification failed for transaction'));

        $job = new SendNotificationJob($transaction);

        // WHEN
        $job->failed(new Exception('Test error'));
    }
}
