<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingIciciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciGatewayTestData.php';

        parent::setUp();

        // removed disable hdfc

        $this->gateway = 'netbanking_icici';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testPayment()
    {
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
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $paymentAction = 'Auth';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $content = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefundFileGeneration()
    {
        // Make 3 test payments
        $this->testPayment();

        $this->testPayment();

        $this->testPayment();

        $payments = $this->getEntities('payment', [], true);

        // We are saying that createdAt = yesterday at 10:30 AM
        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)->addMinutes(30)->timestamp;

        // Set payment dates to yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $createdAt,
                                                              'authorized_at' => $createdAt + 10,
                                                              'captured_at' => $createdAt + 20]);
        }

        // Set the transactions to be reconciled today
        $transactions = $this->getEntities('transaction', [], true);

        // ReconciledAt = today @ 05:13 am
        $reconciledAt = Carbon::today('Asia/Kolkata')->addHours(5)->addMinutes(13)->timestamp;

        foreach ($transactions['items'] as $transaction)
        {
            $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $reconciledAt]);
        }

        // there are 3 test payments. Select the last one to refund. Index 2 = payment 3
        $lastPayment = $payments['items'][2];

        // Refund Re.1 first - partial
        // $refundPayment = $this->refundPayment($lastPayment['id'], 100);

        // Refund the remaining amount ---- fails here
        $refundPayment = $this->refundPayment($lastPayment['id']);

        // sd($refundPayment);

        //sd('111');
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
