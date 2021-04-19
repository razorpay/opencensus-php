<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Federal;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingFederalGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped("Gateway migrated entirely to Nbplus with new integration which is not present on api");

        $this->testDataFilePath = __DIR__.'/NetbankingFederalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_federal';

        $this->bank = 'FDRL';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_federal_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
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

    public function testVerifyAmountMismatch()
    {
        $this->mockAmountMismatch();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

    }

    public function testTpvPayment()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $this->terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    /**
     * Test a payment that was tampered with in the authorize step
     * This case should throw PaymentVerificationException during verify broken
     */
    public function testTamperedPayment()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedVerifyResponse();

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

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $this->testAuthorizeFailed();

        $data = $this->testData['testVerifyMismatch'];

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifySuccessEntity');
    }

    public function testAuthFailedVerifyFailed()
    {
        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailedVerifyResponse();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    /**
     * When the payment is incorrectly marked as authorized
     * and verify points out that it is not
     */
    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData['testVerifyMismatch'];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailedVerifyResponse();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');
    }

    public function testExcelRefundFileGeneration()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $this->createRefundsForFileGeneration($payments);

        $data = $this->generateRefundsExcelForNb($this->bank);

        $this->checkRefundFileData($data['netbanking_federal']);

        $this->checkMailQueue();
    }

    public function testEmptyExcelRefundFileGeneration()
    {
        $this->createPaymentsToClaim();

        $data = $this->generateRefundsExcelForNb($this->bank);

        // Refund file is never generated as count is 0
        $this->assertEmpty($data['netbanking_federal']['refunds']);
    }

    /**
     * This tests the handling of the case when there's two verify tables in the response
     * One table contains a success response and the other contains failure
     * The verify logic should handle this smoothly, with no fuss
     */
    public function testPaymentVerifyExtraParameters()
    {
         $payment = $this->doAuthAndCapturePayment($this->payment);

         $this->mockVerifyExtraParameters();

         $verify = $this->verifyPayment($payment['id']);

         $this->assertEquals(1, $verify['payment']['verified']);
    }

    /**
     * This tests the handling of the case when there's two verify tables in the response
     * One table contains a success response and the other contains success as well
     * The verify logic should throw an exception for this case
     */
    public function testPaymentVerifyTwoSuccessTables()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockVerifyExtraParameters('S');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });
    }

     protected function mockVerifyExtraParameters($status = 'N')
     {
         $this->mockServerContentFunction(
             function(& $content, $action = null) use ($status)
             {
                 $content = explode("\n", $content);

                 unset($content[1]);
                 $content = $content[0];

                 $content .= "\n" . $content;

                 $content[strlen($content) - 1] = $status;
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
        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id']);

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

    protected function checkMailQueue()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $testData = [
            'subject' => 'Federal Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 1500,
                    'refunds' => 1000,
                    'total'   => 500,
                ],
                'count'   => [
                    'claims'  => 3,
                    'refunds' => 3,
                ]
        ];

        Mail::assertQueued(DailyFileMail::class, function ($mail) use ($testData, $date)
        {
            $expectedSubject = 'Federal Netbanking claims and refund files for ' . $date;

            $subject = $mail->subject;

            $this->assertEquals($expectedSubject, $subject);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return $mail->hasTo('federal.netbanking.refunds@razorpay.com');
        });
    }

    protected function checkRefundFileData($data)
    {
        // Asserting that the file exists
        $this->assertTrue(file_exists($data['refunds']));

        $refundsFileContents = file($data['refunds']);

        // 3 refunds + 0 initial line
        assert(count($refundsFileContents) === 3);

        // Individual refund amounts to be asserted
        $refundAmounts = ['100', '400', '500'];

        foreach ($refundsFileContents as $row)
        {
            $refundsFileRow = explode('|', $row);

            // Asserting that the file contains 7 columns
            assert(count($refundsFileRow) === 7);

            // Asserting Free Field
            assert($refundsFileRow[3] === '00000000');

            // Asserting Bank Payment Id
            assert($refundsFileRow[4] === '99999999');

            // Asserting that the refund amounts are correct
            $rowRefundAmount = trim($refundsFileRow[6]);
            assert(in_array($rowRefundAmount, $refundAmounts, true));
        }

        unlink($data['refunds']);
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = "||||";
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['PAID'] = 'N';
            }
        });
    }

    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                // gateway responds with single string like: "AKS68pCw9uqnKu|AKS68PCW9UQNKU|99999999|500|S"
                // To mock AmountMismatch, 500 is replaced with 300. -5 is index from end.
                $content = substr_replace($content, "300|S", -5);
            }
        });
    }
}
