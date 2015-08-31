<?php

namespace Tests\Functional\Payment;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class TerminalTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
//        $this->testDataFilePath = __DIR__.'/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testBilldeskGatewayOnSharedTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');

        // Create all shared terminals
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['gateway'], 'billdesk');
        $this->assertEquals($payment['terminal_id'], '1000BdeskTrmnl');
    }
}
