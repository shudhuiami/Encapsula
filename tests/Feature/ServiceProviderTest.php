<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Feature;

use Illuminate\Routing\Router;
use Zobayer\Encapsula\Contracts\Encryptor;
use Zobayer\Encapsula\EncapsulaServiceProvider;
use Zobayer\Encapsula\Services\ResponseEncryptor;
use Zobayer\Encapsula\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $this->assertArrayHasKey(
            EncapsulaServiceProvider::class,
            $this->app->getLoadedProviders()
        );
    }

    public function test_config_is_merged(): void
    {
        $config = $this->app['config']->get('encapsula');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('key', $config);
        $this->assertArrayHasKey('algorithm', $config);
        $this->assertArrayHasKey('exclude', $config);
        $this->assertArrayHasKey('envelope', $config);
    }

    public function test_encryptor_is_bound(): void
    {
        $encryptor = $this->app->make(Encryptor::class);

        $this->assertInstanceOf(ResponseEncryptor::class, $encryptor);
    }

    public function test_encryptor_is_singleton(): void
    {
        $first = $this->app->make(Encryptor::class);
        $second = $this->app->make(Encryptor::class);

        $this->assertSame($first, $second);
    }

    public function test_middleware_alias_registered(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('encapsula.encrypt', $middleware);
    }
}
