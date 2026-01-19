<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\TransactionCompleted;
use App\Listeners\LogTransactionListener;
use App\Listeners\SendNotificationListener;
use App\Services\AuthorizationService;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransferServiceInterface;
use App\Services\TransferService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthorizationServiceInterface::class,
            AuthorizationService::class
        );

        $this->app->bind(
            TransferServiceInterface::class,
            TransferService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $events): void
    {
        $events->listen(
            TransactionCompleted::class,
            SendNotificationListener::class
        );

        $events->listen(
            TransactionCompleted::class,
            LogTransactionListener::class
        );
    }
}
