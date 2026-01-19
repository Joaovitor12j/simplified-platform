<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Jobs\SendNotificationJob;
use Illuminate\Contracts\Bus\Dispatcher;

class SendNotificationListener
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    public function handle(TransactionCompleted $event): void
    {
        $this->dispatcher->dispatch(
            (new SendNotificationJob($event->transaction))->onQueue('default')
        );
    }
}
