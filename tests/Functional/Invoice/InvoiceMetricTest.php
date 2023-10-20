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

        $mock->expects($this->exactly(15))
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
                    'account_service_check_write_flow_result',
                    1,
                    [
                        'routeOrWorkerName' => 'invoice_fetch_multiple',
                        'isWriteFlow'  => false
                    ],
                ],
                [
                    'account_service_check_exclusion_flow_result',
                    1,
                    [
                        'routeOrWorkerName' => 'invoice_fetch_multiple',
                        'isExclusionFlow'  => false
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
                    'entity_retrieved',
                    1,
                    [
                        'entity' => 'merchant',
                        'rzp_internal_app_name'  => 'none',
                        'route'    => 'invoice_fetch_multiple',
                        'async_job_name' => ""
                    ],
                ],
                [
                    'authenticated_using_passport_total',
                    1,
                    [
                        'route'    => 'invoice_fetch_multiple',
                        'passport_auth' => true,
                        'passport_auth_type' => 'merchant_auth_without_impersonation',
                        'host' => 'api.razorpay.com',
                        'route_type' => 'private'
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
                    'dcs_feature_fetch_total',
                    1,
                    [
                        'feature_name' => 'many',
                        'mode'  => 'test',
                        'function'    => 'getDcsEnabledFeatures',
                    ],
                ],
                [
                    'sdk_usage',
                    1,
                    [
                        'user_agent' => 'Razorpay-not-sdk',
                    ],
                ],
                [
                    'http_requests_total',
                    1,
                    $expectedHttpMetricTags,
                ]);

        $mock->expects($this->at(4))
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
                        'auth_flow_type' => 'key'
                    ],
                ]);

        $mock->expects($this->at(5))
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

        $mock->expects($this->at(4))
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
                        'auth_flow_type' => 'key'
                    ],
                ]);

        $mock->expects($this->at(5))
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
