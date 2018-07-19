<?php

namespace RZP\Tests\Traits;

use Metrics;

trait TestsMetrics
{
    /**
     * Mocks metrics facade
     * @return Metrics
     */
    protected function createMetricsMock(array $methods = ['count', 'gauge', 'histogram', 'summary'])
    {
        $mock = $this->getMockBuilder(Metrics::class)->setMethods($methods)->getMock();

        $this->app->instance('metrics', $mock);

        return $mock;
    }
}
