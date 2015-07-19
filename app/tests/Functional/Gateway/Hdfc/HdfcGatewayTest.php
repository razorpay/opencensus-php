<?php

namespace Tests\Functional\Gateway\Hdfc;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class HdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'hdfc';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

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

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);
    }

    public function testPaymentVerify()
    {
        $this->markTestSkipped();
        $payment = $this->doAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
    }

    public function testHdfcEntityAfterPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hdfc', true);//sd($refund);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->defaultAuthPayment();

        $this->refundAuthorizedPayment($payment['id']);

        $hdfcEntity = $this->getLastEntity('hdfc', true);
        $this->assertEquals('authorized', $hdfcEntity['status']);
        $this->assertEquals('APPROVED', $hdfcEntity['result']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);
    }
}
