<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Canara;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Canara;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingCanaraGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingCanaraGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_canara';

        $this->bank = 'CNRB';

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_canara_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $paymententity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymententity);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $netbankingentity);
    }

    public function testTamperedPayment()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedVerifyResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->doNetbankingCanaraAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $payment = $this->doNetbankingCanaraAuthAndCapturePayment();
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        $this->assertEquals($verify['payment']['verified'], 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }


    public function testRefund()
    {
        $refund = $this->doNetbankingCanaraAuthCaptureAndRefundPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 50000); // have to change
        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testPartialRefund()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        // Refund the payment above partially
        $refund = $this->refundPayment($payment['id'], 10000);   // have to change values here

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
        $this->assertEquals($refund['amount'], 10000);
    }

    public function testRefundExcelFile()
    {

        Mail::fake();

        // Generate 2 payments
        $this->createRefunds();

        $this->alterPaymentsDateToYesterday();

        $this->alterRefundsDateToYesterday();

        // Generating 3rd payment and leaving its created_at
        // date to now unlike first 2 payments
        $this->doNetbankingCanaraAuthCaptureAndRefundPayment();

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForNB('CNRB');

        $this->checkRefundFileData($data);

        $this->checkMailQueue();
    }

    public function doNetbankingCanaraAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
    public function doNetbankingCanaraAuthCaptureAndRefundPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $refund = $this->doAuthCaptureAndRefundPayment($payment);

        return $refund;
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Canara\ResponseFields::RETURN_CODE]   = Canara\Constants::SAMPLE_FAILURE_CODE;
                $content[Canara\ResponseFields::VERIFY_STATUS] = Canara\Constants::SAMPLE_FAILURE_VERIFY_STATUS;
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                unset($content[Canara\ResponseFields::BANK_REFERENCE_NUMBER]);
            }
        });
    }


    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[Canara\ResponseFields::VER_AMOUNT] = '300';
            }
        });
    }

    protected function createRefunds()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);
    }

    protected function alterPaymentsDateToYesterday()
    {
        $payments = $this->getEntities('payment', [], true);
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(30)->timestamp;

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $createdAt]);
        }
    }

    protected function alterRefundsDateToYesterday()
    {
        // Get all pending refunds
        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(45)->timestamp;

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_canara']['file'];

        $this->assertEquals($data['netbanking_canara']['count'], 3);

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refundAmounts = [1 => 50000, 2 => 10000, 3 => 40000];

        $columns = [
                    'TRANSACTION DATE AND TIME',
                    'Refund Date',
                    'BANK_REF_NO',
                    'PG_REF_NUM',
                    'Refund Reference',
                    'Transaction Amount',
                    'Refund Amount'];

        foreach ($refundsFileContents as $key => $row)
        {
            $refundsFileRow = explode('|', $row);

            $lastValue = array_pop($refundsFileRow);

            $lastValue = str_replace(array("\n", "\r"), '', $lastValue);

            array_push($refundsFileRow, $lastValue);

            if($key === 0)
            {
                $this->assertEquals($refundsFileRow, $columns);
            }
            else
            {
                $this->assertEquals($refundsFileRow[6], $refundAmounts[$key]);
            }

            // Asserting that the file contains 7 columns
            $this->assertEquals(count($refundsFileRow), 7);
        }

        unlink($filePath);
    }

    //have to verify what values to enter
    protected function checkMailQueue()
    {
        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $body = 'Please find attached refunds information for Canara Bank';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('emails.message', $mail->view);

            return true;
        });
    }

}