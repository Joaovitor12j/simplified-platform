<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return $next($request);
        }

        $cacheKey = "idempotency:$idempotencyKey";
        $lockKey = "idempotency:$idempotencyKey:lock";

        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            return response()->json($cachedResponse['content'], $cachedResponse['status']);
        }

        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return response()->json(['message' => 'Request conflict/processing'], 429);
        }

        try {
            $response = $next($request);

            if ($response->isSuccessful()) {
                $content = $response->getContent();
                $decodedContent = json_decode($content, true);

                Cache::put($cacheKey, [
                    'content' => $decodedContent ?? $content,
                    'status' => $response->getStatusCode(),
                ], now()->addDay());
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
