<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Options;

class NetbankingIciciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_icici';

        $this->payment = $this->getDefaultNetbankingPaymentArray();

        // Setting it manually because default is IDIB
        $this->payment['bank'] = 'ICIC';

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        // Asserts that bank payment id exists in response and is an int
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

    public function testRefundExcelFile()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund the payment above in full
        $refund = $this->refundPayment($payment['id']);

        // Create a new payment #2
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Do a partial refund of 10000 of payment #2
        $refund = $this->refundPayment($payment['id'], 10000);
        // Refund the remaining amount of the 2nd payment
        $refund = $this->refundPayment($payment['id']);

        // Get all pending refunds
        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        // Generating 3rd payment and leaving its created_at date to now unlike payments 1 and 2
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id']);

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForIciciNB();

        // Data shows 3 refunds - payment 1 = full, payment 2 = 100 and 400. Payment 3 doesn't show up
        $this->assertEquals($data['netbanking_icici']['count'], 3);
        $this->assertTrue(file_exists($data['netbanking_icici']['file']));
    }

    public function testTPVPayment()
    {
        $this->fixtures->create('terminal:shared_netbanking_icici_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $order = $this->startTest();
        $order = $this->getLastEntity('order');

        $this->payment['order_id'] = $order['id'];

        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // Asserting that TPV terminal of ICICI gets picked and not regular
        $this->assertEquals($payment['terminal_id'], '100NbIcicTpvTl');

        $this->fixtures->merchant->disableTPV();
    }
}
