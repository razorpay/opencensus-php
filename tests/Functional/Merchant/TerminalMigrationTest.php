<?php

namespace RZP\Tests\Functional\Merchant;


use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;


class TerminalMigrationTest extends TestCase
{
    use PaymentTrait;

    protected $razorxValue = RazorXClient::DEFAULT_CASE;

    protected $merchant;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalMigrationTestData.php';

        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return $this->razorxValue;

                }) );

        $this->merchant = $this->fixtures->create('merchant');

        $this->ba->adminAuth();
    }

    public function testAssignTerminalTerminalServiceUpRazorxOn()
    {

        $url = '/merchants/'. $this->merchant->getKey(). '/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
