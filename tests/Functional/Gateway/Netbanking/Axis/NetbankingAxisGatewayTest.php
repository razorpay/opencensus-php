<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis;

use Mail;
use Mockery;
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

        $this->mockSetBankPaymentId();

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
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)
                                                      ->addMinutes(30)
                                                      ->timestamp;

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                                                              'authorized_at' => $createdAt + 10,
                                                              'captured_at'   => $createdAt + 20]);
        }

        // Set the transactions to be reconciled today
        $transactions = $this->getEntities('transaction', [], true);

        $reconciledAt = Carbon::today('Asia/Kolkata')->addHours(5)
                                                     ->addMinutes(13)
                                                     ->timestamp;

        foreach ($transactions['items'] as $transaction)
        {
            $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $reconciledAt]);
        }

        // Refund a payment
        $lastPayment = $payments['items'][2];

        $this->refundPayment($lastPayment['id'], 100);

        $this->refundPayment($lastPayment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        // Mail catch with amount and refund everywhere
        Mail::shouldReceive('queue')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $date = Carbon::today('Asia/Kolkata')->format('d-m-Y');

                            $testData = array(
                                'subject' => 'Axis Netbanking claims and refund files for '.$date,
                                'amount' => [
                                    'claims' => 1500,
                                    'refunds' => 500,
                                    'total' => 1000,
                                ]);

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );

        $data = $this->generateRefundsExcelForNB('UTIB');

        $this->assertTrue(file_exists($data['netbanking_axis'][0]));

        $this->assertTrue(file_exists($data['netbanking_axis'][1]));

        $refundsFileContents = file($data['netbanking_axis'][0]);

        $claimsFileContents = file($data['netbanking_axis'][1]);

        // 2 refunds + 1 initial line
        assert(count($refundsFileContents) === 3);

        // 3 claims + 1 initial line
        assert(count($claimsFileContents) === 4);

        $refundsFileLine1 = explode('~~', $refundsFileContents[1]);

        // Each line should have 9 columns
        assert(count($refundsFileLine1) === 9);

        $claimsFileLine1 = explode('~~', $refundsFileContents[1]);

        // Each line should have 8 columns
        assert(count($claimsFileLine1) === 9);
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

        $this->mockVerifyStatusFailure();

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

    protected function mockVerifyStatusFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content['PaymentStatus'] = 'F';
        });
    }

    protected function mockSetBankPaymentId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $payment = $this->getLastEntity('netbanking', true);

            $content['BID'] = $payment['bank_payment_id'];
        });
    }
}
