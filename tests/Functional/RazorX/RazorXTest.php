<?php

namespace RZP\Tests\Functional\RazorX;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class RazorXTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $razorX;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RazorXTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->razorX = $this->getMockBuilder(RazorXClient::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['sendRequest'])
                             ->getMock();
    }

    public function testGetVariantCookieValue()
    {
        $newCookieValue  = RazorXClient::appendVariantToCurrRazorxCookieValue('localUniqueId', 'variant');

        $newCookieValArr = json_decode($newCookieValue, true);

        $this->assertEquals(1, count($newCookieValArr));

        $this->assertEquals('variant',$newCookieValArr['localUniqueId']);
    }

    public function testAppendVariantToCurrRazorxCookieValue()
    {
        $currCookieValArr = [];
        $currCookieValArr['currKey'] = 'currValue';

        $currCookieValStr = json_encode($currCookieValArr);

        $newCookieValue  = RazorXClient::appendVariantToCurrRazorxCookieValue('localUniqueId',
                                                                             'variant',
                                                                             $currCookieValStr);

        $newCookieValArr = json_decode($newCookieValue, true);

        $this->assertEquals(2, count($newCookieValArr));

        $this->assertEquals('currValue', $newCookieValArr['currKey']);
        $this->assertEquals('variant', $newCookieValArr['localUniqueId']);
    }

    public function testGetTreatment()
    {
        $route = 'evaluate';

        $requestParams = [
            'id'           => '10000000000000',
            'feature_flag' => 'reportsV3',
            'environment'  => 'testing',
            'mode'         => 'test',
            'retry_count'  => 0
        ];

        $this->razorX->expects($this->once())
                     ->method('sendRequest')
                     ->with($route, 'GET', $requestParams)
                     ->willReturn('control');

        $variant = $this->razorX->getTreatment('10000000000000', 'reportsV3', 'test');
        
        $this->assertEquals('control', $variant);
    }

    public function testGetTreatmentWithCookies()
    {
        $testData = & $this->testData[__FUNCTION__];
        $uniqueLocalId = RazorXClient::getLocalUniqueId('123','dummy','mode');
        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"new_cookie_flow"}'];

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
