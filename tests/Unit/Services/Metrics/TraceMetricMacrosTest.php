<?php

namespace RZP\Tests\Unit\Services\Metrics;

use Razorpay\Trace\Facades\Trace;

use RZP\Tests\TestCase;
use RZP\Tests\Traits\TestsMetrics;

class TraceMetricMacrosTest extends TestCase
{
    use TestsMetrics;

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
}
