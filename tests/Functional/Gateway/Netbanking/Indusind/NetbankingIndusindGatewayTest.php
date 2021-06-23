<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Indusind;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIndusindGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIndusindGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_indusind';

        $this->bank = 'INDB';

        $this->payment = $this->getDefaultNetbankingPaymentArray('INDB');

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
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

        $this->assertEquals($gatewayPayment['bank_payment_id'], $payment['reference1']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['AMT'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_indusind_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $bank_account = $this->getLastEntity('bank_account', true);

        // removing the order bank account association
        // payment should work without association too
        $this->fixtures->edit('bank_account',
            $bank_account['id'],
            [
                'entity_id' => 'ba_randomorder',
            ]);

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
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
        Mail::fake();

        // Generate 2 payments
        $this->createRefundsForExcel();

        $this->alterRefundsDateToYesterday();

        $this->setPaymentsCreatedAtYesterday();

        // Generating 3rd payment and leaving its created_at
        // date to now unlike first 2 payments
        $this->doAuthCaptureAndRefundPayment($this->payment);

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForNB($this->bank);

        $this->checkRefundFileData($data);

        $this->checkMailQueue();
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

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_indusind']['refunds'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refundAmounts = ['500.00', '100.00', '400.00'];

        foreach ($refundsFileContents as $row)
        {
            $refundsFileRow = explode('|', $row);

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
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function setPaymentsCreatedAtYesterday()
    {
        // Set the transactions to be reconciled today
        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['authorized_at' => $createdAt]);
        }
    }

    protected function checkMailQueue()
    {
        Mail::assertQueued(DailyFileMail::class, function ($mail)
        {
            $this->assertEquals('3', $mail->viewData['count']['refunds']);

            $this->assertEquals('2', $mail->viewData['count']['claims']);

            return true;
        });
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
