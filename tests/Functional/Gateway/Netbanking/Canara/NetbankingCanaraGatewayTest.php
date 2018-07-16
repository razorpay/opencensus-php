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

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

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
        $this->createRefundsForExcel();

        $this->alterRefundsDateToYesterday();

        // Generating 3rd payment and leaving its created_at
        // date to now unlike first 2 payments
        $this->doNetbankingCanaraAuthCaptureAndRefundPayment();

        // Hitting the refunds route on API - goes to RefundFile.php
        $data = $this->generateRefundsExcelForNB('CNRB');

        $this->checkRefundFileData($data);

        //$this->checkMailQueue();
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
                $content = [
                    Canara\ResponseFields::VERIFY_STATUS => Canara\Constants::SAMPLE_FAILURE_VERIFY_STATUS
                ];
            }
        });
    }

    protected function createRefundsForExcel()
    {
        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doNetbankingCanaraAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);
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

    protected function checkRefundFileData($data)
    {
        $filePath = $data['netbanking_canara']['file'];

        $this->assertEquals($data['netbanking_icici']['count'], 3);
        $this->assertTrue(file_exists($filePath));

        $sheet = Excel::load($filePath)->all()->toArray();

        $this->assertEquals(count($sheet[0]), 10);

        // have to change values
        $this->assertEquals($sheet[0]['refund_amount'], 500);
        $this->assertEquals($sheet[1]['refund_amount'], 100);
        $this->assertEquals($sheet[2]['refund_amount'], 400);

        unlink($filePath);
    }

    //have to verify what values to enter
    protected function checkMailQueue()
    {
        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $body = '';

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals('', $mail->viewData['amount']);

            $this->assertEquals('3', $mail->viewData['count']);

            $this->assertEquals('', $mail->view);

            return true;
        });
    }

}