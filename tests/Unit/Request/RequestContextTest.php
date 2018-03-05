<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;
use RZP\Exception\BadRequestException;

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

    public function testPublicRouteWhenKeyIsOfInvalidLen()
    {
        $this->expectException(BadRequestException::class);

        $requestMock = $this->invokeRequestCase('publicRouteWhenKeyIsOfInvalidLen');

        $context = new Helpers\RequestContext;
        $context->initRequestContextVars($requestMock);
    }

    protected function initRequestContextAndAssertForCase(string $case)
    {
        $requestMock = $this->invokeRequestCase($case);

        $context = new Helpers\RequestContext;
        $context->initRequestContextVars($requestMock);

        $caseTestData = $this->testData[$case];
        $expected = $caseTestData['expected'] ?? $this->testData[$caseTestData['expected_same_as']]['expected'];

        foreach ($expected as $key => $value)
        {
            $this->assertEquals($value, $context->$key);
        }
    }
}
