<?php
namespace RZP\Tests\Unit\Models\MerchantAnalytics;

use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Analytics\DataProcessor;
use RZP\Exception\BadRequestValidationFailureException;

class AnalyticsTest extends TestCase
{
    /**
     * @var Merchant\Core
     */
    protected $core;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AnalyticsTestData.php';

        parent::setUp();

        $this->core = (new Merchant\Core);

        $this->validator = (new Merchant\Validator());

        $this->dataProcessor = new DataProcessor();
    }

    public function testAdditionOfMerchantIdFilterInInput()
    {
        $this->startTestForFilter(...$this->testData[__FUNCTION__]);
    }

    public function testAnalyticsInputEmptyFilter()
    {
        $this->startTestForFilter(...$this->testData[__FUNCTION__]);
    }

    public function testAnalyticsInputOverrideMerchantId()
    {
        $this->startTestForFilter(...$this->testData[__FUNCTION__]);
    }

    public function testAnalyticsInputNoFilter()
    {
        $this->startTestForFilter(...$this->testData[__FUNCTION__]);
    }

    public function testAnalyticsIndustryLevelQueryInputWhenInvalidAggregationIsPassed()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->validator->validateCheckoutQueries($this->testData[__FUNCTION__]);
    }

    public function testAnalyticsIndustryLevelQueryInputWhenInvalidFilterIsPassed()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->validator->validateCheckoutQueries($this->testData[__FUNCTION__]);
    }

    /**
     * Industry level filters can only be used with industry level aggregations.
     * Validator will throw BadRequestValidationFailureException if other aggregation uses industry level filter.
     */
    public function testAnalyticsQueryInputWhenInvalidFilterKeyIsPassed()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->validator->validateCheckoutQueries($this->testData[__FUNCTION__]);
    }

    public function testAnalyticsQueryWhenIndustryLevelQueryInputExpectsDefaultMerchantNoAdded()
    {
        $merchantId = '10000000000000';

        $this->validator->validateCheckoutQueries($this->testData[__FUNCTION__]['input']);

        $actual = $this->core->processMerchantAnalyticsQuery($merchantId, $this->testData[__FUNCTION__]['input']);

        $expected = $this->testData[__FUNCTION__]['expected_response'];

        $this->assertEquals($expected, $actual);
    }

    public function testMerchantAnalyticsSrQuery()
    {
        $response = $this->dataProcessor->processMerchantAnalyticsResponse($this->testData['sr_pinot_response']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testMerchantAnalyticsCrQuery()
    {
        $response = $this->dataProcessor->processMerchantAnalyticsResponse($this->testData['cr_pinot_response']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertEquals($expectedResponse, $response);
    }
    public function testMerchantAnalyticsErrorMetricsMethodLevelQuery()
    {
        $response = $this->dataProcessor->processMerchantAnalyticsResponse($this->testData['error_metrics_pinot_response']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testMerchantAnalyticsErrorMetricsOverAllLevelQuery()
    {
        $response = $this->dataProcessor->processMerchantAnalyticsResponse($this->testData['error_metrics_overall_pinot_response']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertEquals($expectedResponse, $response);
    }

    // -------------------- Protected methods --------------------

    protected function startTestForFilter(array $input, array $expected)
    {
        $merchantId = '10000000000000';

        $actual = $this->core->processMerchantAnalyticsQuery($merchantId, $input);

        $this->assertArraySelectiveEquals($expected, $actual);
    }
}
