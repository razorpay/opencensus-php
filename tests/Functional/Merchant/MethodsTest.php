<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MethodsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MethodsTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'billdesk',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $terminal = $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();

        $count = count($content['netbanking']);
        $this->assertEquals(60, $count);
    }

    public function testGetPaymentMethodsRouteWithNetbankingFalse()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->disableNetbanking('10000000000000');

        $content = $this->startTest();

        $count = count($content['netbanking']);
        $this->assertEquals(0, $count);
    }

    public function testNumOfBanksInTestMode()
    {
        $this->ba->publicTestAuth();

        $content = $this->getPaymentMethods();

        $count = count($content['netbanking']);
        $this->assertEquals(60, $count);
    }

    public function testBulkMethodUpdate()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');
       
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);
        
        $this->ba->appAuth();
        
        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        $this->assertEquals($content['card'], true);
        $this->assertEquals($content['netbanking'], true);
        $this->assertEquals($content['mobikwik'], true);
        $this->assertNotEquals($content['banks'], null);
    }

}
