<?php

namespace RZP\Tests\Functional\Helpers;

use Razorpay\Metrics\Manager;
use RZP\Tests\Unit\Mock\Metric;

trait MocksMetricTrait
{
    protected $metricManager = null;

    protected function mockMetricDriver($driver): Metric\Driver
    {
        $mocked = (new Metric\Manager(['default' => 'mock']))->mockDriver($driver);

        app('trace')->setMetricsManager($mocked);

        return $mocked->driver($driver);
    }
}
