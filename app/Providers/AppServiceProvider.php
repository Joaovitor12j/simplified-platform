<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\TransactionCompleted;
use App\Listeners\SendNotificationListener;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Eloquent\EloquentTransactionRepository;
use App\Repositories\Eloquent\EloquentWalletRepository;
use App\Services\AuthorizationService;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransferServiceInterface;
use App\Services\TransferService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            WalletRepositoryInterface::class,
            EloquentWalletRepository::class
        );

        $this->app->bind(
            TransactionRepositoryInterface::class,
            EloquentTransactionRepository::class
        );

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
    public function boot(): void
    {
        Event::listen(
            TransactionCompleted::class,
            SendNotificationListener::class
        );
    }
}
