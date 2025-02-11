<?php

namespace OCharai\Idempotency\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class IdempotencyService
{
    private const string PREFIX = 'idempotency';

    public function __construct(
        private Repository $config,
        private AuthManager $authManager
    ) {
        $this->authManager->guard($this->config->get('auth.defaults.guard'));
    }

    public function get(string $key, string $method): ?array
    {
        $stored = Cache::get($this->generateKey($key, $method));

        return $stored ? json_decode($stored, true) : null;
    }

    private function generateKey(string $key, string $method): string
    {
        return sprintf(
            '%s:%s:%s:%s',
            self::PREFIX,
            $this->authManager->user()?->getAttribute('id') ?? 'global',
            $method,
            $key
        );
    }

    public function store(string $key, string $method, Response $response): void
    {
        if (!$this->shouldStoreResponse($response)) {
            return;
        }

        $data = [
            'response'  => $response->getData(),
            'status'    => $response->getStatusCode(),
            'timestamp' => Carbon::now(),
        ];

        Cache::put(
            $this->generateKey($key, $method),
            json_encode($data),
            Carbon::now()->addSeconds($this->config->get('idempotency.ttl'))
        );
    }

    private function shouldStoreResponse(Response $response): bool
    {
        return $response instanceof JsonResponse
            && $response->isSuccessful();
    }

    public function exists(string $key, string $method): bool
    {
        return Cache::has($this->generateKey($key, $method));
    }
}
