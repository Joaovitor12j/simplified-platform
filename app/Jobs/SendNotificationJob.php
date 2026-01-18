<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function handle(): void
    {
        $response = Http::timeout(5)->post('https://util.devi.tools/api/v1/notify');

        if ($response->failed()) {
            $response->throw();
        }

        Log::info('Notificação enviada com sucesso', [
            'transaction_id' => $this->transaction->id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notification failed for transaction '.$this->transaction->id, [
            'error' => $exception->getMessage(),
            'transaction' => $this->transaction->toArray(),
        ]);
    }
}
