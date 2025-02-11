<?php

namespace OCharai\Idempotency\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use OCharai\Idempotency\Middleware\IdempotencyMiddleware;
use OCharai\Idempotency\Providers\IdempotencyServiceProvider;
use Orchestra\Testbench\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    /** @test */
    public function it_allows_get_requests_without_idempotency_key(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertStatus(200)
            ->assertJson(['message' => 'retrieved']);
    }

    /** @test */
    public function it_requires_idempotency_key_for_post_requests(): void
    {
        $response = $this->postJson('/api/test', ['data' => 'test']);

        $response->assertStatus(400)
            ->assertJson(['message' => 'invalid idempotence header key']);
    }

    /** @test */
    public function it_validates_ulid_format(): void
    {
        $response = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => 'invalid-key',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'invalid idempotence header key']);
    }

    /** @test */
    public function it_processes_request_with_valid_ulid(): void
    {
        $response = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => Str::ulid()->toString(),
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    /** @test */
    public function it_returns_cached_response_for_same_key(): void
    {
        $idempotencyKey = Str::ulid()->toString();

        $firstResponse = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $secondResponse = $this->postJson('/api/test', ['data' => 'different'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $firstResponse->assertStatus(200)
            ->assertJson(['message' => 'success']);

        $secondResponse->assertStatus(200)
            ->assertJson(['message' => 'success']);

        $this->assertEquals(
            $firstResponse->getContent(),
            $secondResponse->getContent()
        );
    }

    /** @test */
    public function it_stores_different_responses_for_different_http_methods(): void
    {
        $idempotencyKey = Str::ulid()->toString();

        $postResponse = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $putResponse = $this->putJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $postResponse->assertStatus(200)
            ->assertJson(['message' => 'success']);

        $putResponse->assertStatus(200)
            ->assertJson(['message' => 'updated']);

        $this->assertNotEquals(
            $postResponse->getContent(),
            $putResponse->getContent()
        );
    }

    /** @test */
    public function it_respects_ttl_configuration(): void
    {
        $idempotencyKey = Str::ulid()->toString();

        $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        Carbon::setTestNow(now()->addMinutes(6));

        $response = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    /** @test */
    public function it_handles_failed_responses_correctly(): void
    {
        $this->app['router']->post('/api/error', fn() => response()->json(['error' => 'failed'], 500))
            ->middleware(IdempotencyMiddleware::class);

        $idempotencyKey = Str::ulid()->toString();

        $firstResponse = $this->postJson('/api/error', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $secondResponse = $this->postJson('/api/error', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $firstResponse->assertStatus(500)
            ->assertJson(['error' => 'failed']);

        $secondResponse->assertStatus(500)
            ->assertJson(['error' => 'failed']);
    }

    /** @test */
    public function it_handles_authenticated_requests_differently(): void
    {
        $idempotencyKey = Str::ulid()->toString();

        $user = new User;
        $user->id = 1;
        $this->actingAs($user);

        $firstResponse = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        auth()->logout();

        $secondResponse = $this->postJson('/api/test', ['data' => 'test'], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $firstResponse->assertStatus(200);
        $secondResponse->assertStatus(200);
    }

    /** @test */
    public function it_handles_non_json_responses_correctly(): void
    {
        $this->app['router']->post(
            '/api/html',
            fn() => response('<html><body>Hello</body></html>', 200, ['Content-Type' => 'text/html'])
        )
            ->middleware(IdempotencyMiddleware::class);

        $idempotencyKey = Str::ulid()->toString();

        $response = $this->post('/api/html', [], [
            'X-Idempotency-Key' => $idempotencyKey,
            'Accept'            => 'text/html',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        $secondResponse = $this->post('/api/html', [], [
            'X-Idempotency-Key' => $idempotencyKey,
            'Accept'            => 'text/html',
        ]);

        $secondResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function getPackageProviders($app): array
    {
        return [
            IdempotencyServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Carbon::setTestNow();
    }

    protected function defineRoutes($router): void
    {
        $router->post('/api/test', fn() => response()->json(['message' => 'success']))
            ->middleware(IdempotencyMiddleware::class);

        $router->put('/api/test', fn() => response()->json(['message' => 'updated']))
            ->middleware(IdempotencyMiddleware::class);

        $router->patch('/api/test', fn() => response()->json(['message' => 'patched']))
            ->middleware(IdempotencyMiddleware::class);

        $router->delete('/api/test', fn() => response()->json(['message' => 'deleted']))
            ->middleware(IdempotencyMiddleware::class);

        $router->get('/api/test', fn() => response()->json(['message' => 'retrieved']))
            ->middleware(IdempotencyMiddleware::class);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('idempotency.ttl', 300);

        // Use array cache for testing
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);
    }
}