<?php

namespace Tests\Functional\Payment;

use EE\Exception\RuntimeException;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class TerminalSelectionTest extends TestCase
{
    use PaymentTrait;

    public function testChoiceGatewayWithSharedTerminals()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        // Create all shared terminals
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        // ICIC should be served with billdesk under these conditions
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('billdesk', $payment['gateway']);
        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

}
