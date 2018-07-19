<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Allahabad;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Allahabad\RefundFile;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class NetbankingAllahabadGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAllahabadGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_allahabad';

        $this->bank = 'ALLA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->getLastEntity('terminal', true);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

//        $this->assertArraySelectiveEquals(
//            $this->testData['testPaymentNetbankingEntity'], $payment
//        );

        $this->assertArrayHasKey('bank', $payment);

    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testAmountTampering()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockAmountTampering();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testPaymentFailedNetbankingEntity');

    }

    public function testFailedChecksum()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedChecksum();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            }
        );

        $gatewayPayment = $this->getLastEntity('netbanking',true);

        $this->assertTestResponse($gatewayPayment,'testPaymentFailedNetbankingEntity');

    }


    public function testExcelRefundFileGeneration()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $this->createRefundsForFileGeneration($payments);

        $data = $this->generateRefundsExcelForNb($this->bank);

        $this->checkRefundFileData($data['netbanking_allahabad']);

        $this->checkMailQueue();
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['PAID'] = "N";
            }
        });
    }

    protected function mockAmountTampering()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['AMT'] = 100;
            }
        });
    }

    protected function mockFailedChecksum()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content['bank_signature'] = 10000000000000000000000;
            }
        });
    }


    protected function createPaymentsToClaim()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
            ->addMinutes(30)
            ->timestamp;

        // Ensuring that the created at timestamps are for yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                'authorized_at' => $createdAt + 10,
                'captured_at'   => $createdAt + 20]);
        }

        return $payments;
    }

    protected function createRefundsForFileGeneration($payments)
    {
        $payment = $payments['items'][0];

        // refund full payment in 2 steps
        $this->refundPayment($payment['id'], 10010);
        $this->refundPayment($payment['id'],35020);

        $payment = $payments['items'][1];

        // refunding in full
        $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
            ->addMinutes(45)
            ->timestamp;

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkRefundFileData($data)
    {
        $this->assertTrue(file_exists($data['file']));

        $refundsFileContents = file($data['file']);

        // 3 refunds + 0 initial line
        assert(count($refundsFileContents) === 3);

        // Individual refund amounts to be asserted
        $refundAmounts = ['100.10', '350.20', '500.00'];

        foreach ($refundsFileContents as $row)
        {
            $refundsFileRow = explode('|', $row);

            // Asserting that the file contains 10 columns
            assert(count($refundsFileRow) === 10);

            $rowRefundAmount = trim($refundsFileRow[9]);

            assert(in_array($rowRefundAmount, $refundAmounts, true));
        }

        //unlink($data['file']);
    }


    protected function checkMailQueue()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $testData = [
            'body'    => "Please find attached refunds information for Allahabad Netbanking",
            'count'   => 3,
        ];

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($testData, $date)
        {
            $expectedSubject = 'Allahabad Netbanking refunds file for '.$date;

            $subject = $mail->subject;

            $this->assertEquals($expectedSubject, $subject);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return $mail->hasTo('settlements@razorpay.com');
        });
    }






}