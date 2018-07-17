<?php

namespace RZP\Tests\Unit\Services\Metrics;

use Metrics;
use Razorpay\Trace\Facades\Trace;

use RZP\Tests\TestCase;

class TraceMetricMacrosTest extends TestCase
{
    /**
     * Asserts that macros being called on trace are calling valid instance & methods of Metrics service
     * @dataProvider getTraceMetricTestCaseProvider
     */
    public function testTraceMetricMacros(string $macro, array $args)
    {
        $this->createMetricsMock()
             ->expects($this->once())
             ->method($macro)
             ->with(...$args);

        Trace::$macro(...$args);
    }

    public function getTraceMetricTestCaseProvider(): array
    {
        return require_once __DIR__ . '/helpers/TraceMetricMacrosTestData.php';
    }

    /**
     * Creates mock for Metrics facade and register as instance
     * @return Metrics
     */
    protected function createMetricsMock(): Metrics
    {
        $mock = $this->getMockBuilder(Metrics::class)
                     ->setMethods(['count', 'gauge', 'histogram', 'summary'])
                     ->getMock();

        $this->app->instance('metrics', $mock);

        return $mock;
    }
}
