<?php

namespace RZP\Tests\Functional\Merchant;

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
}
