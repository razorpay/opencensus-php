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

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testPayment()
    {
        $paymentAction = 'AuthAndCapture';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $payment = $this->getLastEntity('payment', true);

        // Setting terminal manually
        $payment['terminal_id'] = '100NbIciciTmnl';

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($payment['payment_id']), $payment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testTrialGatewayHighChancePayment()
    {
        $chance = 95; // testing a chance value of 95

        Options::setTestChance($chance);

        // Adding it to the test case
        $terminal = $this->fixtures->create('terminal:netbanking_icici_terminal');

        $paymentAction = 'AuthAndCapture';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($payment['payment_id']), $payment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testTrialGatewayLowChancePayment()
    {
        $chance = 87; // testing a chance value of 90

        Options::setTestChance($chance);

        // Adding it to the test case
        $terminal = $this->fixtures->create('terminal:netbanking_icici_terminal');

        $paymentAction = 'AuthAndCapture';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $payment = $this->getLastEntity('payment', true);

        // 100NbiciciTmnl won't be picked for chance = 87
        // Shared Terminal will be picked - so not asserting for terminal_id
        $this->assertTestResponse($payment);

        // Making sure that the shared terminal gets picked and not ICICI
        $this->assertTrue($payment['terminal_id'] !== '100NbIciciTmnl');

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($payment['payment_id']), $payment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'],
            FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $paymentAction = 'Auth';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $content = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefundExcelFile()
    {
        // Do an Auth and Capture of a payment
        $paymentAction = 'AuthAndCapture';

        $payment = $this->doNetbankingIciciPayment($paymentAction);

        // Refund the payment above in full
        $refund = $this->refundPayment($payment['id']);

        // Create a new payment #2
        $payment = $this->doNetbankingIciciPayment($paymentAction);

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
        $payment = $this->doNetbankingIciciPayment($paymentAction);
        // refunding it
        $this->refundPayment($payment['id']);

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForIciciNB();

        // Data shows 3 refunds - payment 1 = full, payment 2 = 100 and 400. Payment 3 doesn't show up

        $this->assertEquals($data['netbanking_icici']['count'], 3);
        $this->assertTrue(file_exists($data['netbanking_icici']['file']));
    }

    protected function doNetbankingIciciPayment($paymentAction)
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'ICIC';

        // Switch case
        switch ($paymentAction)
        {
            case 'Auth':
                $payment = $this->doAuthPayment($payment);
                break;

            default:
                $payment = $this->doAuthAndCapturePayment($payment);
                break;
        }

        return $payment;
    }

}
