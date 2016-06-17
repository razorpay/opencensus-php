<?php

namespace Tests\Functional\Gateway\Cybersource;

use EE\Exception;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Gateway\Cybersource;
use Tests\Functional\TestCase;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cybersource_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->setMockGatewayTrue();

        $this->mockTokenex();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $amount = $payment['amount'];
        $payment = $this->doAuthPayment($payment);

        $payment = $this->capturePayment($payment['razorpay_payment_id'], $amount);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('cybersource', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentCybersourceEntity'], $payment);
    }

    public function testMasterCardPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthPayment($payment);
    }

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('cybersource', true);

        $this->assertTestResponse($refund);
    }
}
