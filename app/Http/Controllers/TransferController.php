<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferServiceInterface $transferService
    ) {
    }

    /**
     * Handle the incoming transfer request.
     *
     * @param TransferRequest $request
     * @return JsonResponse
     */
    public function store(TransferRequest $request): JsonResponse
    {
        try {
            $payer = User::findOrFail($request->validated('payer'));
            $payee = User::findOrFail($request->validated('payee'));

            $transaction = $this->transferService->execute(
                $payer,
                $payee,
                (string) $request->validated('value')
            );

            SendNotificationJob::dispatch($transaction)->onQueue('default');

            return response()->json($transaction, Response::HTTP_CREATED);
        } catch (Throwable $e) {
            $code = $e->getCode();

            $statusCode = match (true) {
                $code >= 400 && $code < 600 => (int) $code,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
                Log::error('Erro ao processar transferência: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
