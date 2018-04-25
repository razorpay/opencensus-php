<?php

namespace RZP\Tests\Functional\RazorX;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class RazorXTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $razorX;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RazorXTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->razorX = $this->getMockBuilder(RazorXClient::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['sendRequest'])
                             ->getMock();
    }

    public function testGetTreatment()
    {
        $route = 'evaluate';

        $requestParams = [
            'id'           => '10000000000000',
            'feature_flag' => 'reportsV3',
            'environment'  => 'testing',
            'mode'         => 'test',
        ];

        $this->razorX->expects($this->once())
             ->method('sendRequest')
             ->with($route, 'GET', $requestParams)
             ->willReturn('control');


        $variant = $this->razorX->getTreatment('10000000000000', 'reportsV3', 'test');

        $this->assertEquals('control', $variant);
    }

    public function testGetTreatmentWithHeaders()
    {
        $headers = ['10000000000000_dummy_testing_test' => 'new_header_flow'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server'] = ['HTTP_X-RazorX-Variant' => base64_encode(json_encode($headers))];

        $this->startTest();
    }

    public function testGetTreatmentWithCookies()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['cookies'] = ['10000000000000_dummy_testing_test' => 'new_cookie_flow'];

        $this->startTest();
    }

    public function testGetTreatmentFallbackToHeaders()
    {
        $headers = ['10000000000000_dummy_testing_test' => 'fallback_header_flow'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['cookies'] = ['10000000000022_dummy_testing_test' => 'new_cookie_flow'];

        $testData['request']['server'] = ['HTTP_X-RazorX-Variant' => base64_encode(json_encode($headers))];

        $this->startTest();
    }

    public function testGetTreatmentFallbackToService()
    {
        $headers = ['10000000000022_dummy_testing_test' => 'new_header_flow'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server'] = ['HTTP_X-RazorX-Variant' => base64_encode(json_encode($headers))];

        $this->startTest();
    }
}
