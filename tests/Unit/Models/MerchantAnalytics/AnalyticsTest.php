<?php
namespace RZP\Tests\Unit\Models\MerchantAnalytics;

use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;

class AnalyticsTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AnalyticsTestData.php';

        parent::setUp();

        $this->core = (new Merchant\Core);
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

    // -------------------- Protected methods --------------------

    protected function startTestForFilter(array $input, array $expected)
    {
        $merchantId = '10000000000000';

        $actual = $this->core->validateInputFiltersAndAddMerchantId($merchantId, $input);

        $this->assertArraySelectiveEquals($expected, $actual);
    }
}