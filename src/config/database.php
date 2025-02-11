<?php

return [
    'redis' => [
        'idempotency' => [
            'client'   => env('REDIS_CLIENT', 'redis'),
            'cluster'  => env('REDIS_QUEUE_CLUSTER', false),
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'options'  => [
                'prefix' => "idempotency_",
            ],
        ],
    ],
];