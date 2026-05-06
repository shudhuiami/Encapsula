<?php

declare(strict_types=1);

namespace Zobayer\Encapsula;

use Illuminate\Support\ServiceProvider;

class EncapsulaServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        // Package config is merged instead of overwritten so host applications can override only the values they need.
        $this->mergeConfigFrom(
            __DIR__.'/../config/encapsula.php',
            'encapsula'
        );
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
    }
}
