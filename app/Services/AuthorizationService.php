<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Domain\UnauthorizedTransactionException;
use App\Services\Contracts\AuthorizationServiceInterface;
use Exception;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Serviço responsável pela autorização de transações externas.
 */
final readonly class AuthorizationService implements AuthorizationServiceInterface
{
    private string $url;

    public function __construct(
        private LoggerInterface $logger,
        private HttpFactory $http
    ) {
        $this->url = 'https://util.devi.tools/api/v2/authorize';
    }

    /**
     * @throws UnauthorizedTransactionException
     */
    public function authorize(): bool
    {
        try {
            $response = $this->http->timeout(5)->retry(2, 100, null, false)->get($this->url);

            if ($response->failed()) {
                $this->logger->warning('External authorization service failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new UnauthorizedTransactionException('Serviço de autorização externo indisponível.');
            }

            $data = $response->json();

            if (! isset($data['data']['authorization']) || $data['data']['authorization'] !== true) {
                throw new UnauthorizedTransactionException;
            }

            return true;
        } catch (UnauthorizedTransactionException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error connecting to external authorization service', [
                'message' => $e->getMessage(),
            ]);

            throw new UnauthorizedTransactionException('Erro ao conectar ao serviço de autorização externo.');
        }
    }
}
