<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Indusind;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Options;

class NetbankingIndusindGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIndusindGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_indusind';

        $this->bank = 'INDB';

        $this->payment = $this->getDefaultNetbankingPaymentArray('INDB');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefund()
    {
        $refund = $this->doAuthCaptureAndRefundPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 50000);
        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Refund the payment above partially
        $refund = $this->refundPayment($payment['id'], 10000);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
        $this->assertEquals($refund['amount'], 10000);
    }

    public function testFailedRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                // Refund double the amount
                $refund = $this->refundPayment($payment['id'], 100000);
            });
    }

    public function testRefundExcelFile()
    {
        // Generate 2 payments
        $this->createRefundsForExcel();

        $this->alterRefundsDateToYesterday();

        // Generating 3rd payment and leaving its created_at
        // date to now unlike first 2 payments
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $this->checkMailQueue();

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForNB($this->bank);

        $this->checkRefundFileData($data);
    }


    public function testFailedAuthPayment()
    {
        $this->mockPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    public function testVerifyMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->doAuthPayment($this->payment);

        $this->mockVerifyFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            });
    }

    public function testAuthResponseDecryptionFailure()
    {
        $this->mockAuthDecryptionFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    // Authorization fails, but verify shows success
    // Results in a payment verification error
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

    protected function createRefundsForExcel()
    {
        // Refund the payment above in full
        $refund = $this->doAuthCaptureAndRefundPayment($this->payment);

        // Create a new payment #2
        $payment = $this->doAuthAndCapturePayment($this->payment);

        // Do a partial refund of 10000 of payment #2
        $this->refundPayment($payment['id'], 10000);
        // Refund the remaining amount of the 2nd payment
        $this->refundPayment($payment['id']);
    }

    protected function alterRefundsDateToYesterday()
    {
        // Get all pending refunds
        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_indusind']['file'];

        // Data shows 3 refunds - payment 1 = full, payment 2 = 100 and 400. Payment 3 doesn't show up
        $this->assertEquals($data['netbanking_indusind']['count'], 3);
        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refundAmounts = ['500', '100', '400'];

        foreach ($refundsFileContents as $row)
        {
            $refundsFileRow = explode('||', $row);

            // Asserting that the file contains 5 columns
            assert(count($refundsFileRow) === 6);

            // Asserting Bank Payment Id
            assert(trim($refundsFileRow[5]) === '9999999999');

            // Asserting that the refund amounts are correct
            $rowRefundAmount = $refundsFileRow[4];

            assert(in_array($rowRefundAmount, $refundAmounts));
        }

        unlink($filePath);
    }

    protected function checkMailQueue()
    {
         // Mail catch with amount and refund everywhere
        Mail::shouldReceive('queue')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                    {
                        $body = 'Please forward the Indusind Netbanking refunds file to UBPS operations team';

                        $this->assertEquals($body, $data['body']);

                        return true;
                    }),
                    Mockery::any()
                );
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
            $content['VERIFICATION'] = 'N';
        });
    }

    protected function mockAuthDecryptionFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'hash')
            {
                $content['RQS'] = '0123456789ABCDEF';
            }
        });
    }
}
