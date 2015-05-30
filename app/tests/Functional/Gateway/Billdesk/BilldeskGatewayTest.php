<?php

namespace Tests\Functional\Gateway\Billdesk;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class PaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BilldeskGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'billdesk';
    }

    public function testPayment()
    {
        $this->markTestIncomplete();
        $this->config['gateway.mock_billdesk'] = true;

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }
}
