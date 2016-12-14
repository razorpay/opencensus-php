<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingAxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAxisGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_axis';

        $this->payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_axis_terminal');
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);

        $this->assertTrue(filter_var($payment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment($this->payment);

        $content = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefundInFull()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals($refund['amount'], 50000);
    }

    public function testPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $refund = $this->refundPayment($payment['id'], 10000);

        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment){
            $refund = $this->refundPayment($payment['id'], 100000);
        });
    }

    public function testRefundFileGeneration()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund the above payment in full
        $refund = $this->refundPayment($payment['id']);

        // Create a new payment #2
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // do a partial refund of the above payment
        $refund = $this->refundPayment($payment['id'], 10000);
        // refund the remaining amount in the payment
        $refund = $this->refundPayment($payment['id']);

        // Get all the pending refunds
        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday so that they are
        // picked up during txt file generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;
            // Find out how this works using sd's
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsFileForAxisNB();

        $this->assertEquals($data['netbanking_axis']['count'], 3);
        // assert that total amount is 1000
        $this->assertEquals($data['netbanking_axis']['file'][0], 1000);
        $this->assertTrue(file_exists($data['netbanking_axis']['file'][1]));
    }

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

    // Auth fails but verify shows success
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment){
            $this->verifyPayment($payment['id']);
        });
    }

    protected function mockPaymentFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['PAID'] = 'N';
        });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['PaymentStatus'] = 'F';
        });
    }
}
