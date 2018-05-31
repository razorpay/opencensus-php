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

        $mock->expects($this->exactly(7))
             ->method('count')
             ->withConsecutive(
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
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
                    'http_request_duration_microseconds',
                    $this->greaterThanOrEqual(0),
                    $expectedHttpMetricTags,
                ]);

        $this->startTest();
    }

    public function testCreateInvoiceAndAssertMetricsSent()
    {
        $expectedHttpMetricTags = $this->testData[__FUNCTION__ . 'ExpectedMetricTags'];

        $mock = $this->createMetricsMock();

        $mock->expects($this->exactly(12))
             ->method('count')
             ->withConsecutive(
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'key',
                    ],
                ],
                [
                    'eloquent_cache_misses_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                    ],
                ],
                [
                    'eloquent_cache_writes_total',
                    1,
                    [
                        'version' => 'v1',
                        'entity'  => 'merchant',
                    ],
                ],
                [
                    'traces_total',
                    1,
                    [
                        'code'       => 'INVOICE_CREATE_REQUEST',
                        'level'      => 200,
                        'level_name' => 'INFO',
                        'channel'    => 'Razorpay API',
                        'route'      => 'invoice_create',
                        'rzp_mode'   => 'test',
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
                    'traces_total',
                    1,
                    [
                        'code'       => 'ES_SYNC_REQUEST',
                        'level'      => 100,
                        'level_name' => 'DEBUG',
                        'channel'    => 'Razorpay API',
                        'route'      => 'invoice_create',
                        'rzp_mode'   => 'test',
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
                    'traces_total',
                    1,
                    [
                        'code'       => 'INVOICE_CREATED',
                        'level'      => 200,
                        'level_name' => 'INFO',
                        'channel'    => 'Razorpay API',
                        'route'      => 'invoice_create',
                        'rzp_mode'   => 'test',
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
                    'http_request_duration_microseconds',
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
