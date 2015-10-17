<?php

namespace Tests\Functional\Gateway\AxisMigs;

use Mockery;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $payment);
    }

    public function testFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertTestResponse($refund);
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testMaestroOnMigs()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');
    }
}
