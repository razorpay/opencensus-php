<?php

namespace RZP\Tests\Traits;

use Razorpay\Metrics\Manager as MetricManager;

trait TestsMetrics
{
    protected function createMetricsMock(array $methods = ['count', 'gauge', 'histogram', 'summary'])
    {
        $mock = $this->getMockBuilder(MetricManager::class)
                     ->setMethods($methods)
                     ->getMock();

        $this->app['trace']->setMetricsManager($mock);

        return $mock;
    }

    public function mockAndCaptureCountMetric(string $metricNameToCapture, $metricsMock, bool &$metricCaptured, $expectedMetricData)
    {
        $closure = function($metricName, $times, $actualMetricData) use ($expectedMetricData, & $metricCaptured, $metricNameToCapture) {
            $actual   = ['metric_name' => $metricName, 'metric_data' => $actualMetricData];
            $expected = ['metric_name' => $metricNameToCapture, 'metric_data' => $expectedMetricData];
            $this->validateMetricData($metricNameToCapture, $expected, $actual, $metricCaptured);
        };

        $metricsMock->method('count')
                    ->will($this->returnCallback($closure));
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
