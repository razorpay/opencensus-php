<?php

namespace Tests\Functional\Gateway\Sharp;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class SharpGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/SharpGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testPaymentFailed()
    {
        $this->failPaymentOnBankPage = true;

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();
        $testData['request']['content'] = $payment;
        $this->startTest($testData);
    }
}
