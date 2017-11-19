<?php

namespace RZP\Tests\Unit\Services;

use RZP\Exception;
use RZP\Tests\TestCase;
use RZP\Services\Geolocation\Service;

class GeolocationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('services.geolocation.mocked', true);
    }

    public function testExceptionForInvalidProvider()
    {
        $this->expectException(Exception\InvalidArgumentException::class);

        $this->getGeolocationService('invalid');
    }

    public function testMockedFlowForEureka()
    {
        $geolocation = $this->getGeolocationService('eureka');

        $geolocation = $geolocation->getGeolocation('106.51.22.240');

        $this->assertSame('Bangalore', $geolocation['city']);
    }

    public function testMockedFailureForEureka()
    {
        $geolocation = $this->getGeolocationService('eureka');

        $geolocation = $geolocation->getGeolocation('127.0.0.1');

        $this->assertNull($geolocation);
    }

    public function testExceptionInvalidKeyForEureka()
    {
        $geolocation = $this->getGeolocationService('eureka');

        $this->expectException(Exception\InvalidArgumentException::class);

        $geolocation->validateAndSetInput([
            'eureka_key_index' => -1
        ]);
    }

    public function testExceptionCriticalErrorForEureka()
    {
        $geolocation = $this->getGeolocationService('eureka');

        $this->expectException(Exception\RuntimeException::class);

        $geolocation = $geolocation->getGeolocation('0.0.0.0');

        $this->assertNull($geolocation);
    }

    /*
     * Helpers
     */

    protected function getGeolocationService(string $provider): Service
    {
        $this->app['config']->set('services.geolocation.provider', $provider);

        return $this->app['geolocation'];
    }
}
