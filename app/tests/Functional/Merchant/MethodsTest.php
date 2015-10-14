<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

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
        $this->assertEquals(56, $count);
    }

    public function testNumOfBanksInTestMode()
    {
        $this->ba->publicTestAuth();

        $content = $this->getPaymentMethods();

        $count = count($content['netbanking']);
        $this->assertEquals(61, $count);
    }
}
