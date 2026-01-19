<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferServiceInterface $transferService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle the incoming transfer request.
     */
    public function store(TransferRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transferService->execute(
                $request->toDTO()
            );

            return response()->json($transaction, Response::HTTP_CREATED);
        } catch (Throwable $e) {
            $code = $e->getCode();

            $statusCode = match (true) {
                $code >= 400 && $code < 600 => (int) $code,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
                $this->logger->error('Erro ao processar transferência: '.$e->getMessage(), [
                    'exception' => $e,
                ]);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
