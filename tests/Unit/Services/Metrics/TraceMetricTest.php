<?php

namespace RZP\Tests\Unit\Services\Metrics;

use Razorpay\Trace\Facades\Trace;

use Metrics;
use RZP\Tests\TestCase;
use RZP\Trace\TraceCode;

class TraceMetricTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TraceMetricTestData.php';

        parent::setUp();
    }

    /**
     * Sets expectation on Metrics service and does many tracing and asserts.
     */
    public function testTracesAreSendingMetrics()
    {
        $this->markTestSkipped();
        $this->createMetricsMock()
             ->expects($this->exactly(3))
             ->method('count')
             ->withConsecutive(...$this->testData[__FUNCTION__]);

        Trace::info(TraceCode::PAYMENT_NEW_REQUEST, ['amount' => '100']);
        Trace::critical(TraceCode::PAYMENT_AUTH_FAILURE);
        Trace::traceException(new \Exception('Something went wrong!'));
    }

    /**
     * Creates mock for Metrics facade and register as instance
     * @return Metrics
     */
    protected function createMetricsMock(): Metrics
    {
        $mock = $this->getMockBuilder(Metrics::class)
                     ->setMethods(['count', 'gauge', 'histogram'])
                     ->getMock();

        $this->app->instance('metrics', $mock);

        return $mock;
    }
}
