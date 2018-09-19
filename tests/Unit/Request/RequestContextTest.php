<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;
use RZP\Http\RequestContext;

class RequestContextTest extends TestCase
{
    use Traits\HasRequestCases;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/RequestContextTestData.php';

        parent::setUp();
    }

    /**
     * For all available request cases assert that proper context
     * vars are being set.
     */
    public function testAllPositiveRequestCases()
    {
        $requestCases = array_keys($this->testData);
        foreach ($requestCases as $case)
        {
            $this->initRequestContextAndAssertForCase($case);
        }
    }

    protected function initRequestContextAndAssertForCase(string $case)
    {
        $this->invokeRequestCase($case);

        $context = new RequestContext($this->app);
        $context->init();

        $testDataExpected = $this->testData[$case]['expected'];
        foreach ($testDataExpected as $key => $expected)
        {
            $accessor = 'get' . ucfirst($key);
            $actual = $context->$accessor();
            $this->assertEquals($expected, $actual);
        }
    }
}
