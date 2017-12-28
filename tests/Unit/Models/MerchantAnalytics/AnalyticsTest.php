<?php
namespace RZP\Tests\Unit\Models\MerchantAnalytics;

use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;

class AnalyticsTest extends TestCase
{
    const INPUT_INDEX = 0;
    const EXPECTED_OUTPUT_INDEX = 1;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AnalyticsTestData.php';

        parent::setUp();

        $this->core = (new Merchant\Core);
    }

    public function testAdditionOfMerchantIdFilterInInput()
    {
        $this->startTestForFilter();
    }

    public function testAnalyticsInputEmptyFilter()
    {
        $this->startTestForFilter();
    }

    public function testAnalyticsInputOverrideMerchantId()
    {
        $this->startTestForFilter();
    }

    public function testAnalyticsInputNoFilter()
    {
        $this->startTestForFilter();
    }

    protected function startTestForFilter()
    {
        $merchantId = '10000000000000';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $input = $testData[self::INPUT_INDEX];

        $actualContent = $this->core->validateInputFiltersAndAddMerchantId($merchantId, $input);

        $expectedContent = $testData[self::EXPECTED_OUTPUT_INDEX];

        $this->assertArraySelectiveEquals($expectedContent, $actualContent);
    }
}