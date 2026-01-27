<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Core\Application\UseCases\TransferServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferServiceInterface $transferService
    ) {}

    /**
     * Handle the incoming transfer request.
     */
    public function store(TransferRequest $request): JsonResponse
    {
        $transaction = $this->transferService->execute($request->toDTO());

        return response()->json($transaction, Response::HTTP_CREATED);
    }
}
