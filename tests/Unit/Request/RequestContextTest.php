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

    public function testPublicRouteWhenKeyIsOfInvalidLen()
    {
        $this->expectException(BadRequestException::class);

        $requestMock = $this->invokeRequestCase('publicRouteWhenKeyIsOfInvalidLen');

        $context = new Helpers\RequestContext;
        $context->initRequestContextVars($requestMock);
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
