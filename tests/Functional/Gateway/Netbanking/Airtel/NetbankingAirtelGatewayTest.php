<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Airtel;

use Carbon\Carbon;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingAirtelGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAirtelGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_airtel';

        $this->payment = $this->getDefaultNetbankingPaymentArray('AIRP');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_airtel_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        // Assert that bank payment id exists and is an integer
        $this->assertArrayHasKey('bank_payment_id', $payment);

        $this->assertTrue(filter_var($payment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund above payment in full
        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals($refund['amount'], $payment['amount']);
    }

    public function testPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund above payment in full
        $refund = $this->refundPayment($payment['id'], 10000);

        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment){
            // Refund double amount
            $refund = $this->refundPayment($payment['id'], 100000);
        });
    }

    // public function testTPVPayment()

    public function testFailedAuthPayment()
    {
        $this->mockPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function(){
            $this->doAuthPayment($this->payment);
        });
    }

    public function testVerifyMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow($data, function() use ($payment){
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    // Authorization fails, but verify shows success
    // Results in a payment verification error
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment){
            $this->verifyPayment($payment['id']);
        });
    }

    public function testFailedRefundStatus()
    {
        $this->mockRefundFailure();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment){
            // Refund double amount
            $refund = $this->refundPayment($payment['id'], 1000);
        });
    }

    public function testAuthResponseHashFailure()
    {
        $this->mockAuthHashFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function(){
            $this->doAuthPayment($this->payment);
        });
    }

    protected function mockPaymentFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['STATUS'] = 'FAL';
        });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['status'] = 'FAL';
        });
    }

    protected function mockAuthHashFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $hash = $content['HASH'];
            $content['HASH'] = str_shuffle($hash);
        });
    }

    protected function mockRefundFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['status'] = 'FAL';
        });
    }
}
