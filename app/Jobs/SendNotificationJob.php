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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Transaction $transaction
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::post('https://util.devi.tools/api/v1/notify');

        if ($response->failed()) {
            throw new \RuntimeException('Failed to send notification');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Notification failed for transaction ' . $this->transaction->id, [
            'error' => $exception->getMessage(),
            'transaction' => $this->transaction->toArray(),
        ]);
    }
}
