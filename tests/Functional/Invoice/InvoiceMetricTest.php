<?php

namespace RZP\Tests\Functional\Invoice;

use Metrics;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceMetricTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceMetricTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testGetMultipleInvoicesAndAssertMetricsSent()
    {
        $expectedHttpMetricTags = $this->testData[__FUNCTION__ . 'ExpectedMetricTags'];

        $mock = $this->createMetricsMock();

        $mock->expects($this->exactly(5))
             ->method('count')
             ->withConsecutive(
                [
                    'cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'http_requests_total',
                    1,
                    $expectedHttpMetricTags,
                ]);

        $mock->expects($this->once())
             ->method('histogram')
             ->withConsecutive(
                [
                    'http_request_duration_milliseconds.histogram',
                    $this->greaterThanOrEqual(0),
                    $expectedHttpMetricTags,
                ]);

        $this->startTest();
    }

    public function testCreateInvoiceAndAssertMetricsSent()
    {
        $expectedHttpMetricTags = $this->testData[__FUNCTION__ . 'ExpectedMetricTags'];

        $mock = $this->createMetricsMock();

        $mock->expects($this->exactly(7))
             ->method('count')
             ->withConsecutive(
                [
                    'cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                        'type'    => 'query_cache',
                    ],
                ],
                [
                    'async_jobs_received_total',
                    1,
                    [
                        'async_job_connection' => 'sync',
                        'async_job_queue'      => 'sync',
                        'async_job_name'       => 'RZP_Jobs_EsSync',
                    ],
                ],
                [
                    'async_jobs_processed_total',
                    1,
                    [
                        'async_job_connection' => 'sync',
                        'async_job_queue'      => 'sync',
                        'async_job_name'       => 'RZP_Jobs_EsSync',
                    ],
                ],
                [
                    'http_requests_total',
                    1,
                    $expectedHttpMetricTags,
                ]);

        $mock->expects($this->once())
             ->method('histogram')
             ->withConsecutive(
                [
                    'http_request_duration_milliseconds.histogram',
                    $this->greaterThanOrEqual(0),
                    $expectedHttpMetricTags,
                ]);

        $this->startTest();
    }

    protected function createMetricsMock()
    {
        $mock = $this->getMockBuilder(Metrics::class)
                     ->setMethods(['count', 'gauge', 'histogram'])
                     ->getMock();

        $this->app->instance('metrics', $mock);

        return $mock;
    }
}
