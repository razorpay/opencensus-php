<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($gatewayPayment['payment_id']), $gatewayPayment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayPayment);

        $this->assertArrayHasKey('bank_payment_id', $gatewayPayment);
        $this->assertTrue(filter_var($gatewayPayment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['TxnAmount'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentOnDirectHdfcTerminal()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals($terminal['id'], $payment['terminal_id']);
    }

    public function testPaymentOnSharedTerminal()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayPayment);

        $this->assertArrayHasKey('bank_payment_id', $gatewayPayment);
        $this->assertTrue(filter_var($gatewayPayment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
    }

    public function testRefundExcelFile()
    {
        // Will remove test in separate pr
        $this->markTestSkipped();

        Mail::fake();

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForNb('HDFC');

        $this->assertEquals($data['netbanking_hdfc']['count'], 3);
        $this->assertTrue(file_exists($data['netbanking_hdfc']['file']));

        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $testData = [
                'body' => 'Please forward the HDFC Netbanking refunds file to: Directpay.Refunds@hdfcbank.com',
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function doNetbankingHdfcAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
