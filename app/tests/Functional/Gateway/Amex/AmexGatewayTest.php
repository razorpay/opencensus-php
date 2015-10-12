<?php

namespace Tests\Functional\Gateway\Amex;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class AmexGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AmexGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'amex';

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['card']['number'] = '341111111111111';
        $this->payment['card']['cvv'] = '8888';
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('amex', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAmexEntity'], $payment);
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('amex', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('amex', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->verifyPayment($payment['id']);
    }
}
