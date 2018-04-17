<?php

namespace RZP\Tests\Unit\Services;

use RZP\Tests\TestCase;
use RZP\Services\Metrics\Manager;
use RZP\Services\Metrics\Drivers\Mock;
use RZP\Services\Metrics\Drivers\Dogstatsd;

class ServiceTest extends TestCase
{
    public function testFacadeResolvesToCorrectInstance()
    {
        $this->assertInstanceOf(Manager::class, \Metrics::getFacadeRoot());
    }

    public function testServiceUsageConfiguredMockDriver()
    {
        $this->assertInstanceOf(Mock::class, \Metrics::getFacadeRoot()->driver());
    }

    public function testServiceUsageConfiguredDogstatsdDriver()
    {
        $this->app['config']->set('metrics.default', 'dogstatsd');

        $this->assertInstanceOf(Dogstatsd::class, \Metrics::getFacadeRoot()->driver());
    }
}
