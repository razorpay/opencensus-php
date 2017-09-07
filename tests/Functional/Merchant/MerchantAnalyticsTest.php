<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantAnalyticsTest extends TestCase
{
    use PaymentTrait;

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

    protected function initializeRouteAnalyticsRequest()
    {
        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/10000000000000/analytics';

        // For validations
        $testData['request']['content']['query']['filter']['terms'][0]['merchant_id'] = '10000000000000';

        return $testData;
    }
}
