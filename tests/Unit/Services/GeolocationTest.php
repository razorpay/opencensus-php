<?php

namespace RZP\Tests\Unit\Services;

use RZP\Tests\TestCase;

class GeolocationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('services.geolocation.mocked', true);
    }

    public function testMockedFlowForEureka()
    {
        $this->app['config']->set('services.geolocation.provider', 'eureka');

        $geolocation = $this->app['geolocation'];

        $geolocation = $geolocation->getGeolocation('106.51.22.240');

        $this->assertSame('Bangalore', $geolocation['city']);
    }

    public function testMockedFailureForEureka()
    {
        $this->app['config']->set('services.geolocation.provider', 'eureka');

        $geolocation = $this->app['geolocation'];

        $geolocation = $geolocation->getGeolocation('127.0.0.1');

        $this->assertNull($geolocation);
    }
}
