<?php

namespace RZP\Tests\Traits;

use Razorpay\Metrics\Manager as MetricManager;
use Razorpay\Metrics\Drivers\Mock as MockDriver;

trait TestsMetrics
{
    protected function createMetricsMock(array $methods = ['count', 'gauge', 'histogram', 'summary'])
    {
        $mockDriver = $this->getMockBuilder(Mockdriver::class)
                     ->setConstructorArgs(['api'])
                     ->onlyMethods($methods)
                     ->getMock();
        $mockManager = $this->getMockBuilder(MetricManager::class)
                      ->setConstructorArgs([['default' => 'mock']])
                      ->onlyMethods(['driver'])
                      ->getMock();
        $mockManager->method('driver')
                    ->willReturn($mockDriver);

        $this->app['trace']->setMetricsManager($mockManager);

        return $mockDriver;
    }

    public function mockAndCaptureCountMetric(string $metricNameToCapture, $metricsMock, bool &$metricCaptured, $expectedMetricData)
    {
        $closure = function($metricName, $times, $actualMetricData) use ($expectedMetricData, & $metricCaptured, $metricNameToCapture) {
            $actual   = ['metric_name' => $metricName, 'metric_data' => $actualMetricData];
            $expected = ['metric_name' => $metricNameToCapture, 'metric_data' => $expectedMetricData];
            $this->validateMetricData($metricNameToCapture, $expected, $actual, $metricCaptured);
        };

        $metricsMock->method('count')
                    ->willReturnCallback($closure);
    }

    public function validateMetricData(string $metricName, array $expectedMetricData, array $actualMetricData, bool &$passed)
    {
        $actualMetricName = $actualMetricData['metric_name'];

        if ($actualMetricName !== $metricName)
        {
            return;
        }

        $this->assertArraySelectiveEquals($expectedMetricData, $actualMetricData);

        $passed = true;
    }
}
