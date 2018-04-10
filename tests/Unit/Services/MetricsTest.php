<?php

namespace RZP\Tests\Unit\Services;

use RZP\Tests\TestCase;
use RZP\Services\Metrics\Metrics;
use RZP\Services\Metrics\Drivers\Mock;
use RZP\Services\Metrics\Drivers\Dogstatsd;

class ServiceTest extends TestCase
{
    public function testFacadeResolvesToCorrectInstance()
    {
        $this->assertInstanceOf(Metrics::class, \Metrics::getFacadeRoot());
    }

    public function testServiceUsageConfiguredMockDriver()
    {
        $this->assertInstanceOf(Mock::class, \Metrics::getFacadeRoot()->getCurrentDriver());
    }

    public function testServiceUsageConfiguredDogstatsdDriver()
    {
        $this->app['config']->set('metrics.default', 'dogstatsd');

        $this->assertInstanceOf(Dogstatsd::class, \Metrics::getFacadeRoot()->getCurrentDriver());
    }

    public function testServiceUsageSetDriver()
    {
        $this->assertInstanceOf(Dogstatsd::class, \Metrics::getFacadeRoot()->driver('dogstatsd')->getCurrentDriver());
    }
}
