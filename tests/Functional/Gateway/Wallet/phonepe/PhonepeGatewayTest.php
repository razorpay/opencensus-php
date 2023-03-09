<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Phonepe;

use RZP\Tests\Functional\Payment\NbPlusPaymentServiceWalletTest;

class PhonepeGatewayTest extends NbPlusPaymentServiceWalletTest
{
    const WALLET = 'phonepe';

    protected function setUp(): void
    {
        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_phonepe_terminal');

        $this->fixtures->merchant->enableWallet($this->merchantId, self::WALLET);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);
    }

    public function testPaymentViaNbplus()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('1ShrdPhnepeTrm', $payment['terminal_id']);
    }

    public function testIntentPaymentViaNbplus()
    {
        $payment = $this->payment;

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $this->mockCallbackFromGateway($response['data']['intent_url']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }
}
