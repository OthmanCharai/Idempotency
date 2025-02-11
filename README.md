# Laravel Idempotency

A robust Laravel package that provides idempotency support for your API endpoints with Redis storage. This package helps prevent duplicate operations when retrying API requests.

## Features

- Redis-based storage for idempotency keys
- Configurable TTL for idempotency keys
- Support for multiple HTTP verbs (POST, PUT, PATCH, DELETE)
- User-specific idempotency keys
- Automatic response caching for successful JSON responses
- ULID-based idempotency keys for security and uniqueness

## Requirements

- PHP ^7.4|^8.0|^8.3
- Laravel ^8.0|^9.0|^10.0|^11.0
- Redis server

## Installation

You can install the package via composer:

```bash
composer require ocharai/laravel-idempotency
```

The package will automatically register its service provider.

## Configuration

Publish the configuration files:

```bash
php artisan vendor:publish --provider="OCharai\Idempotency\Providers\IdempotencyServiceProvider"
```

This will create two configuration files:
- `config/idempotency.php`: Main package configuration
- `config/database.php`: Redis connection configuration

### Main Configuration Options

```php
return [
    // The header key used for idempotency
    'header_key' => env('IDEMPOTENCY_HEADER_KEY', 'X-Idempotency-Key'),

    // Time-to-live for idempotency keys (in seconds)
    'ttl' => env('IDEMPOTENCY_TTL', 86400), // 1 day

    // HTTP verbs that require idempotency
    'enforced_verbs' => [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ],

    // Redis configuration
    'redis' => [
        'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'idempotency'),
        'prefix'     => 'idempotency:',
    ],
];
```

### Redis Configuration

```php
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
```

## Usage

1. Add the middleware to your routes or controller:

```php
use OCharai\Idempotency\Middleware\IdempotencyMiddleware;

// In a route
Route::post('/api/orders', [OrderController::class, 'store'])
    ->middleware(IdempotencyMiddleware::class);

// Or in a controller
public function __construct()
{
    $this->middleware(IdempotencyMiddleware::class);
}
```

2. Make API requests with an idempotency key:

```bash
curl -X POST https://your-api.com/api/orders \
    -H "X-Idempotency-Key: 01HNG4V2JXSC0XD6YE4CTWF5QM" \
    -H "Content-Type: application/json" \
    -d '{"product_id": 123}'
```

Note: The idempotency key must be a valid ULID (Universally Unique Lexicographically Sortable Identifier).

### How It Works

1. When a request is made with an idempotency key, the middleware checks if the key exists in Redis
2. If the key exists and the request method matches, the cached response is returned
3. If the key doesn't exist, the request is processed normally
4. On successful JSON responses, the response is cached with the idempotency key
5. Subsequent requests with the same key within the TTL period will receive the cached response

### Important Notes

- Only successful JSON responses (2xx status codes) are cached
- Idempotency keys are scoped to the authenticated user (if any) and HTTP method
- Keys automatically expire after the configured TTL
- Invalid or missing ULID keys will result in a 400 Bad Request response

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email othman.fiver@gmail.com instead of using the issue tracker.

## Credits

- [Othman Charai](https://github.com/OthmanCharai)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.