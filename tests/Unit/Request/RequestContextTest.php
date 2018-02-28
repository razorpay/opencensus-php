<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;

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
    public function testAllRequestCases()
    {
        $requestCases = array_keys($this->testData);
        foreach ($requestCases as $case)
        {
            $this->assertForRequestCase($case);
        }
    }

    protected function assertForRequestCase(string $case)
    {
        $requestMock = $this->invokeRequestCase($case);

        $context = new Helpers\RequestContext;
        $context->initRequestContextVars($requestMock);

        $expected = $this->testData[$case]['expected'];
        foreach ($expected as $key => $value)
        {
            $this->assertEquals($value, $context->$key);
        }
    }
}
