<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que a tarefa pode ser tentada.
     */
    public int $tries = 5;

    /**
     * O número de segundos a aguardar antes de tentar novamente a tarefa.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        public Transaction $transaction
    ) {}

    public function handle(LoggerInterface $logger, HttpFactory $http): void
    {
        $response = $http->timeout(5)->post(config('services.notification.url'));

        if ($response->failed()) {
            $response->throw();
        }

        $logger->info('Notificação enviada com sucesso', [
            'transaction_id' => $this->transaction->id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        app(LoggerInterface::class)->error('Notification failed for transaction '.$this->transaction->id, [
            'error' => $exception->getMessage(),
            'transaction' => $this->transaction->toArray(),
        ]);
    }
}
