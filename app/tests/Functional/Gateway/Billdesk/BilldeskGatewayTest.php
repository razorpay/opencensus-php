<?php

namespace Tests\Functional\Gateway\Billdesk;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class BilldeskGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BilldeskGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'billdesk';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('billdesk', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentBilldeskEntity'], $payment);
    }

    public function testPaymentFailed()
    {
        $this->markTestIncomplete();

        $this->failPaymentOnBankPage = true;
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('billdesk', true);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('billdesk', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentRefund'], $refund);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterRefundingAuthorizedPayment'], $txn);
    }
}
