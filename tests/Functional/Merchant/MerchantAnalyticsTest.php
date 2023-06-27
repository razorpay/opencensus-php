<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Services\Mock\HarvesterClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantAnalyticsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $testDataFilePath;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAnalyticsTestData.php';

        parent::setUp();
    }

    public function testMerchantAnalytics()
    {
        $testData = $this->initializeMerchantAnalyticsRequest();

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsPayment()
    {
        $testData = $this->initializeMerchantAnalyticsRequest();

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsNoFilter()
    {
        $testData = $this->initializeMerchantAnalyticsRequest();

        $this->startTest($testData);
    }

    protected function initializeMerchantAnalyticsRequest()
    {
        $this->ba->proxyAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/analytics';

        return $testData;
    }

    public function testMerchantAnalyticsSrQuery()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $pinotService = $this->getMockBuilder(HarvesterClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $pinotService->method('query')
            ->willReturn($this->testData['sr_pinot_response']);

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsCrQuery()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $pinotService = $this->getMockBuilder(HarvesterClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $pinotService->method('query')
            ->willReturn($this->testData['cr_pinot_response']);

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsErrorMetricsQuery()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $pinotService = $this->getMockBuilder(HarvesterClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $pinotService->method('query')
            ->willReturn($this->testData['error_metrics_pinot_response']);

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsSrQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsCrQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testMerchantAnalyticsErrorMetricsQueryWhenRequiredFieldIsNotPassedExpectsBadRequestException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }
}
