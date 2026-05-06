<?php

declare(strict_types=1);

namespace Zobayer\Encapsula;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Zobayer\Encapsula\Contracts\Encryptor;
use Zobayer\Encapsula\Http\Middleware\EncryptApiResponse;
use Zobayer\Encapsula\Services\ResponseEncryptor;

class EncapsulaServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/encapsula.php',
            'encapsula'
        );

        $this->app->singleton(Encryptor::class, function ($app) {
            /** @var string $key */
            $key = $app['config']->get('encapsula.key', '');

            /** @var string $algorithm */
            $algorithm = $app['config']->get('encapsula.algorithm', 'aes-256-gcm');

            return new ResponseEncryptor($key, $algorithm);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/encapsula.php' => config_path('encapsula.php'),
            ], 'encapsula-config');
        }

        $this->registerMiddlewareAlias();
    }

    /**
     * Register the middleware alias for route-level usage.
     */
    protected function registerMiddlewareAlias(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('encapsula.encrypt', EncryptApiResponse::class);
    }
}
