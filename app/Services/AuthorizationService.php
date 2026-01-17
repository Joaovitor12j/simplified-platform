<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\AuthorizationServiceInterface;
use App\Exceptions\Domain\UnauthorizedTransactionException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço responsável pela autorização de transações externas.
 */
final class AuthorizationService implements AuthorizationServiceInterface
{
    private string $url = 'https://util.devi.tools/api/v2/authorize';

    /**
     * @throws UnauthorizedTransactionException
     */
    public function authorize(): bool
    {
        try {
            $response = Http::get($this->url);

            if ($response->failed()) {
                Log::warning('External authorization service failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new UnauthorizedTransactionException("Serviço de autorização externo indisponível.");
            }

            $data = $response->json();

            if (! isset($data['data']['authorization']) || $data['data']['authorization'] !== true) {
                throw new UnauthorizedTransactionException();
            }

            return true;
        } catch (Exception $e) {
            if ($e instanceof UnauthorizedTransactionException) {
                throw $e;
            }

            Log::error('Error connecting to external authorization service', [
                'message' => $e->getMessage(),
            ]);

            throw new UnauthorizedTransactionException("Erro ao conectar ao serviço de autorização externo.");
        }
    }
}
