<?php

declare(strict_types=1);

namespace Zobayer\Encapsula;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Zobayer\Encapsula\Contracts\Encryptor;
use Zobayer\Encapsula\Console\EncapsulaSetupCommand;
use Zobayer\Encapsula\Http\Middleware\EncryptApiResponse;
use Zobayer\Encapsula\Services\EncryptionKeyResolver;
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

        // Not a singleton because session-mode key is request/session dependent.
        $this->app->bind(EncryptionKeyResolver::class, function ($app) {
            return new EncryptionKeyResolver(
                $app['config'],
                $app->bound('session.store') ? $app->make('session.store') : null,
            );
        });

        $this->app->bind(Encryptor::class, function ($app) {
            /** @var EncryptionKeyResolver $resolver */
            $resolver = $app->make(EncryptionKeyResolver::class);

            /** @var string $algorithm */
            $algorithm = $app['config']->get('encapsula.algorithm', 'aes-256-gcm');

            return new ResponseEncryptor($resolver->getBase64Key(), $algorithm);
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

            $this->commands([
                EncapsulaSetupCommand::class,
            ]);
        }

        if ((bool) config('encapsula.enabled', true) && (bool) config('encapsula.handshake.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/encapsula.php');
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
