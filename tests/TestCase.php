<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zobayer\Encapsula\EncapsulaServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EncapsulaServiceProvider::class,
        ];
    }
}
