<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Feature;

use Zobayer\Encapsula\EncapsulaServiceProvider;
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
        $this->assertArrayHasKey('strict', $config);
        $this->assertArrayHasKey('date_format', $config);
        $this->assertArrayHasKey('validate_by_default', $config);
    }

    public function test_config_default_values(): void
    {
        $this->assertFalse($this->app['config']->get('encapsula.strict'));
        $this->assertSame('Y-m-d H:i:s', $this->app['config']->get('encapsula.date_format'));
        $this->assertTrue($this->app['config']->get('encapsula.validate_by_default'));
    }

    public function test_config_values_can_be_overridden(): void
    {
        $this->app['config']->set('encapsula.strict', true);
        $this->app['config']->set('encapsula.date_format', 'Y-m-d');

        $this->assertTrue($this->app['config']->get('encapsula.strict'));
        $this->assertSame('Y-m-d', $this->app['config']->get('encapsula.date_format'));
    }
}
