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
        $testData = $this->fetchTestData();

        $input = $testData[0];

        $expectedContent = $testData[1];

        $this->startTestForFilter($input, $expectedContent);
    }

    public function testAnalyticsInputEmptyFilter()
    {
        $testData = $this->fetchTestData();

        $input = $testData[0];

        $expectedContent = $testData[1];

        $this->startTestForFilter($input, $expectedContent);
    }

    public function testAnalyticsInputOverrideMerchantId()
    {
        $testData = $this->fetchTestData();

        $input = $testData[0];

        $expectedContent = $testData[1];

        $this->startTestForFilter($input, $expectedContent);
    }

    public function testAnalyticsInputNoFilter()
    {
        $testData = $this->fetchTestData();

        $input = $testData[0];

        $expectedContent = $testData[1];

        $this->startTestForFilter($input, $expectedContent);
    }

    // -------------------- Protected methods --------------------

    protected function fetchTestData()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        return $this->testData[$name];
    }

    protected function startTestForFilter(array $input, array $expectedContent)
    {
        $merchantId = '10000000000000';

        $actualContent = $this->core->validateInputFiltersAndAddMerchantId($merchantId, $input);

        $this->assertArraySelectiveEquals($expectedContent, $actualContent);
    }
}