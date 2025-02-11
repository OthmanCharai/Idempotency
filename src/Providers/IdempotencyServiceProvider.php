<?php

namespace OCharai\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;

class IdempotencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/idempotency.php',
            'idempotency'
        );

        $this->replaceConfigRecursivelyFrom(
            __DIR__ . '/../database.php',
            'database'
        );
    }
}
