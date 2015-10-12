<?php

namespace Tests\Functional\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class NetbankingHdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingHdfcGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_hdfc';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_hdfc_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal');

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($payment['payment_id']), $payment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentOnSharedTerminal()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
    }

    public function testRefundExcelFile()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $id = substr($refund['id'], 5);
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;
            $this->fixtures->edit('refund', $id, ['created_at' => $createdAt]);
        }

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForHdfcNB();

        $this->assertEquals($data['count'], 3);
    }

    protected function doNetbankingHdfcAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'HDFC';
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
