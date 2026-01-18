<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Jobs\SendNotificationJob;

class SendNotificationListener
{
    public function handle(TransactionCompleted $event): void
    {
        SendNotificationJob::dispatch($event->transaction)->onQueue('default');
    }
}
