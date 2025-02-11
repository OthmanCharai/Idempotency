# Laravel Idempotency Package

Add idempotency support to your Laravel APIs easily. This package helps prevent duplicate operations by caching responses based on idempotency keys, particularly useful for payment processing, form submissions, and other critical operations.

# Table of Contents

| Section |
|---------|
| [What is Idempotency?](#what-is-idempotency) |
| [Features](#features) |
| [Installation](#installation) |
| [Configuration](#configuration) |
| [Usage](#usage) |
| [Response Headers](#response-headers) |
| [Examples](#examples) |
| [Redis Inspection](#redis-inspection) |
| [License](#license) |

## What is Idempotency?

Idempotency ensures that an API request produces the same result regardless of how many times it's sent. This is crucial for:
- Payment processing
- Order submissions
- Data updates
- Any operation that should not be duplicated

For example, if a client sends a payment request but loses connection before receiving the response, it can safely retry the request with the same idempotency key. The server will return the original response without processing the payment twice.

## Features

* **Redis-Based Storage**: Fast and reliable response caching using Redis
* **Configurable TTL**: Control how long responses are cached
* **Custom Header Support**: Configure your own idempotency header name
* **Method Filtering**: Apply idempotency only to specific HTTP methods
* **Automatic Redis Configuration**: No manual Redis setup required
* **User Authentication Integration**: Works with Laravel's authentication system

## Installation

```bash
composer require ocharai/laravel-idempotency
```

## Configuration

1. Publish the configuration (optional):
```bash
php artisan vendor:publish --tag=idempotency-config
```

2. Configure in your `.env` file:
```env
# Idempotency Configuration
IDEMPOTENCY_HEADER_KEY=X-Idempotency-Key
IDEMPOTENCY_TTL=86400

# Redis will use your existing Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
```

Available configuration options in `config/idempotency.php`:
```php
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
        'connection' => 'idempotency',
    ],
];
```

## Usage

1. Register the middleware in `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    'idempotent' => \OCharai\Idempotency\Middleware\IdempotencyMiddleware::class,
];
```

2. Use in your routes:
```php
Route::middleware(['idempotent'])->group(function () {
    Route::post('/orders', 'OrderController@store');
});
```

3. Make requests with idempotency key:
```http
POST /api/orders
X-Idempotency-Key: a valid ulid key 
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 5
}
```

## Examples

### Basic Usage
```php
// OrderController.php
public function store(Request $request)
{
    // Your logic here
    // The middleware automatically handles idempotency
    return response()->json(['order_id' => $orderId]);
}
```

### Key Generation (Client Side)
```php
use Symfony\Component\Uid\Ulid;

$idempotencyKey = (new Ulid())->toBase32();
```

## Redis Inspection

Check stored responses in Redis:

```bash
# Connect to Redis
redis-cli

# List all idempotency keys
KEYS idempotency:*

# Get specific response
GET idempotency:your-key-here

# Get TTL
TTL idempotency:your-key-here
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.