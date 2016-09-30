<?php

namespace RZP\Tests\Functional\Gateway\FirstData;

use RZP\Exception;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class FirstDataGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/FirstDataGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'first_data';

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPaymentAuthAndCapture()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->capturePayment($authResponse['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['verified'], 1);
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('txn_'.$payment['transaction_id'], $txn['id']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'refunded');

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    public function testPaymentPartialRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment['status'], 'captured');

        $this->assertEquals($refund['payment_id'], $payment['public_id']);

        $this->assertEquals($refund['amount'], $amount);
    }

    public function testPaymentRefundWithoutCapture()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testPaymentDoubleCapture()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });
    }

    public function testFailedAuthPayment()
    {
        $this->getErrorInAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFailedAuthUnknownError()
    {
        $this->getUnknownErrorInAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testFailedRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInReturn();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundpayment($payment['id']);
        });
    }

    public function testFailedCapture()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInCapture();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });
    }

    public function testFailedVerify()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getErrorInInquiry();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testMismatchVerify()
    {
        $authResponse = $this->doAuthPayment($this->payment);

        $this->fixtures->edit('payment', $authResponse['razorpay_payment_id'], ['status' => 'failed']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($authResponse) {
            $this->verifyPayment($authResponse['razorpay_payment_id']);
        });
    }

    public function testCaptureTimeout()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->getTimeoutInCapture();

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->capturePayment($payment['id'], $payment['amount']);
        });

        $gatewayPayment = $this->getLastEntity('first_data', true);

        $this->assertEquals($gatewayPayment['gateway_payment_id'], null);

        $this->assertEquals($gatewayPayment['received'], false);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }
}
