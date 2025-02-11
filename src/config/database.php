<?php

return [
    'redis' => [
        'idempotency' => [
            'client'   => env('REDIS_CLIENT', 'redis'),
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'prefix'   => 'idempotency:',
        ],
    ],
];