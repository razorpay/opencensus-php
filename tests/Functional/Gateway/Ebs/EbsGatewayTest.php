<?php

namespace RZP\Tests\Functional\Gateway\Ebs;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class EbsGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EbsGatewayTestData.php';
        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'ebs';
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

        $payment = $this->getLastEntity('ebs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentEbsEntity'], $payment);
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterAuthorize'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);
        $this->refundPayment($payment['id']);
        $refund = $this->getLastEntity('ebs', true);
        $this->assertTestResponse($refund);
    }

    public function testPaymentRefundWithoutCapture()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $data = $this->testData['testPaymentRefundWithoutCapture'];

        $payment = $this->doauthpayment($payment);

        $payment = $this->getlastentity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('ebs', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentRefund'], $refund);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterRefundingAuthorizedPayment'], $txn);
    }

    public function testErrorOnCard()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $data = $this->testData['testErrorOnCard'];

        $payment['method'] = 'card';

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testPaymentInvalidRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->capturePayment($payment['public_id'],
            $payment['amount']);

        $this->getErrorInRefund();

        $data = $this->testData['testPaymentInvalidRefund'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id']);
        });

        $refund = $this->getLastEntity('ebs', true);
        $this->assertEquals($refund['error_code'], "29");
        $this->assertEquals($refund['error_description'], "Insufficient balance");
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }

    public function testPaymentFailedVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->getErrorWithInvalidReturnCodeInVerify();
        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testPaymentFailedVerify'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->verifyPayment($payment['id']);
        });

        $this->assertSame($payment['verified'], null);
    }
}
