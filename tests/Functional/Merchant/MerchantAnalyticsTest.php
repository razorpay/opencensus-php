<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantAnalyticsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $testDataFilePath;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAnalyticsTestData.php';

        parent::setUp();
    }

    public function testRouteAnalytics()
    {
        $testData = $this->initializeRouteAnalyticsRequest();

        $this->startTest($testData);
    }

    public function testRouteAnalyticsDeviceValidation()
    {
        $testData = $this->initializeRouteAnalyticsRequest();

        $this->startTest($testData);
    }

    public function testRouteAnalyticsMethodValidation()
    {
        $testData = $this->initializeRouteAnalyticsRequest();

        $this->startTest($testData);
    }

    protected function initializeRouteAnalyticsRequest()
    {
        $this->ba->proxyAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/10000000000000/analytics';

        // For validations
        $testData['request']['content']['query']['filters']['default'][0]['merchant_id'] = '10000000000000';

        return $testData;
    }
}
