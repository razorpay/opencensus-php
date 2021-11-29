<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceMetricTest extends TestCase
{
    use TestsMetrics;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceMetricTestData.php';

        parent::setUp();

        config(['app.query_cache.mock' => false]);

        $this->ba->privateAuth();
    }

    public function testGetMultipleInvoicesAndAssertMetricsSent()
    {
        $expectedHttpMetricTags = $this->testData[__FUNCTION__ . 'ExpectedMetricTags'];

        $mock = $this->createMetricsMock();

        $mock->expects($this->at(15))
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
                     'cache_misses_total',
                     1,
                     [
                         'version' => 'v1',
                         'entity'  => 'feature',
                         'type'    => 'query_cache',
                     ],
                 ],
                 [
                     'cache_writes_total',
                     1,
                     [
                         'version' => 'v1',
                         'entity'  => 'feature',
                         'type'    => 'query_cache',
                     ],
                 ],
                [
                    'http_requests_total',
                    1,
                    $expectedHttpMetricTags,
                ]);

        $mock->expects($this->at(0))
             ->method('histogram')
             ->withConsecutive(
                [
                    'authenticate_handle_milliseconds.histogram',
                    $this->greaterThanOrEqual(0),
                    [
                        'mode'   => 'test',
                        'route'  => 'invoice_fetch_multiple',
                        'auth'   => 'private',
                        'proxy'  => false,
                        'bearer' => false,
                    ],
                ]);

        $mock->expects($this->at(3))
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

        $mock->expects($this->at(0))
             ->method('histogram')
             ->withConsecutive(
                [
                    'authenticate_handle_milliseconds.histogram',
                    $this->greaterThanOrEqual(0),
                    [
                        'mode'   => 'test',
                        'route'  => 'invoice_create',
                        'auth'   => 'private',
                        'proxy'  => false,
                        'bearer' => false,
                    ],
                ]);

        $mock->expects($this->at(1))
             ->method('histogram')
             ->withConsecutive(
                [
                    'http_request_duration_milliseconds.histogram',
                    $this->greaterThanOrEqual(0),
                    $expectedHttpMetricTags,
                ]);

        $this->startTest();
    }
}
