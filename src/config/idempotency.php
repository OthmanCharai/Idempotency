<?php

return [
    'header_key' => env('IDEMPOTENCY_HEADER_KEY', 'X-Idempotency-Key'),

    'ttl' => env('IDEMPOTENCY_TTL', 86400), // 1 day

    'enforced_verbs' => [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ],

    'redis' => [
        'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'idempotency'),
        'host'       => env('IDEMPOTENCY_REDIS_HOST', '127.0.0.1'),
        'port'       => env('IDEMPOTENCY_REDIS_PORT', 6379),
        'database'   => env('IDEMPOTENCY_REDIS_DB', 1),
    ],
];