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
        $this->assertEquals('billdesk', $payment['gateway']);
        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

    public function testHdfcGatewayOnSharedTerminals()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:all_shared_terminals');

        // Create all shared terminals
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
    }
}
