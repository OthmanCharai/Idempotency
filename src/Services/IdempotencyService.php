<?php

namespace OCharai\Idempotency\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class IdempotencyService
{
    private const string PREFIX = 'idempotency';

    public function __construct(
        private RedisManager $redisManager,
        private Repository $config,
        private AuthManager $authManger
    ) {
        $this->redisManager->connection($this->config->get('idempotency.redis.connection'));
        $this->authManger->guard($this->config->get('auth.defaults.guard'));
    }

    // Store Response if not exists.
    public function get(string $key, string $method): ?array
    {
        $stored = $this->redisManager->get($this->generateKey($key, $method));

        return $stored ? json_decode($stored, true) : null;
    }

    // Get Stored Response

    private function generateKey(string $key, string $method): string
    {
        return sprintf(
            '%s:%s:%s:%s',
            self::PREFIX,
            $this->authManger->user()?->getAttribute('id') ?? 'global',
            $method,
            $key
        );
    }

    public function store(string $key, string $method, Response $response): void
    {
        // Store only JsonResponse && Successful response
        if (!$this->shouldStoreResponse($response)) {
            return;
        }

        $data = [
            'response'  => $response->getData(),
            'status'    => $response->getStatusCode(),
            'timestamp' => Carbon::now(),
        ];

        $this->redisManager->setex(
            $this->generateKey($key, $method),
            $this->config->get('idempotency.ttl'),
            json_encode($data)
        );
    }

    private function shouldStoreResponse(Response $response): bool
    {
        return $response instanceof JsonResponse
            && $response->isSuccessful();
    }

    public function exists(string $key, string $method): bool
    {
        return $this->redisManager->exists($this->generateKey($key, $method));
    }
}
