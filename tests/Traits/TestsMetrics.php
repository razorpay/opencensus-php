<?php

namespace RZP\Tests\Traits;

trait TestsMetrics
{
    protected function createMetricsMock(array $methods = ['count', 'gauge', 'histogram', 'summary'])
    {
        $mock = $this->getMockBuilder(Razorpay\Metrics\Manager::class)
                     ->setMethods($methods)
                     ->getMock();

        $this->app['trace']->setMetricsManager($mock);

        return $mock;
    }
}
