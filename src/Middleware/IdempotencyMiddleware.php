<?php

namespace OCharai\Idempotency\Middleware;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OCharai\Idempotency\Services\IdempotencyService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


readonly class IdempotencyMiddleware
{
    public function __construct(
        private IdempotencyService $idempotencyService,
        private Repository $config
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (!in_array($request->getMethod(), $this->config->get('idempotency.enforced_verbs'))) {
            return $next($request);
        }

        $headerKey = $request->headers->get($this->config->get('idempotency.header_key'));

        if (is_null($headerKey) || !Str::isUlid($headerKey)) {
            return new JsonResponse(
                [
                    'message' => 'invalid idempotence header key',
                ], Response::HTTP_BAD_REQUEST
            );
        }

        if ($storedResponse = $this->idempotencyService->get($headerKey, $request->getMethod())) {
            return new JsonResponse(
                Arr::get($storedResponse, 'response'),
                Arr::get($storedResponse, 'status')
            );
        }

        $response = $next($request);

        $this->idempotencyService->store($headerKey, $request->getMethod(), $response);

        return $response;
    }
}
