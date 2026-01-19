<?php

use App\Exceptions\Domain\AuthorizationServiceException;
use App\Exceptions\Domain\InsufficientBalanceException;
use App\Exceptions\Domain\MerchantPayerException;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'idempotency' => IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (InsufficientBalanceException $e) => response()->json([
            'message' => $e->getMessage(),
        ], 422));

        $exceptions->render(fn (UnauthorizedTransactionException $e) => response()->json([
            'message' => $e->getMessage(),
        ], 403));

        $exceptions->render(fn (MerchantPayerException $e) => response()->json([
            'message' => $e->getMessage(),
        ], 400));

        $exceptions->render(fn (AuthorizationServiceException $e) => response()->json([
            'message' => $e->getMessage(),
        ], 502));
    })->create();
