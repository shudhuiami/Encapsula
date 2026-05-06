<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zobayer\Encapsula\EncapsulaServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * A stable test key (32 bytes, base64-encoded). NOT for production.
     */
    protected string $testKey = 'SBKURysvjru6mkCVUrzcwLU32wOexWGIAD+/1ErdX/0=';

    protected function getPackageProviders($app): array
    {
        return [
            EncapsulaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('encapsula.key', $this->testKey);
        $app['config']->set('encapsula.enabled', true);
    }
}
