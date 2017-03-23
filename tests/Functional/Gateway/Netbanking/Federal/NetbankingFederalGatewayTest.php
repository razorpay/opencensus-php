<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Federal;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingFederalGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingFederalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_federal';

        $this->bank = 'FDRL';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_federal_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
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

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
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

        $this->assertTestResponse($gatewayPayment, 'testVerifyFailedNetbankingEntity');
    }

    public function testExcelRefundFileGeneration()
    {
        $payments = $this->createPaymentsToClaim();

        $this->createRefundsForFileGeneration($payments);

        $this->checkMailQueue();

        $data = $this->generateRefundsExcelForNb($this->bank);

        $this->checkRefundFileData($data['netbanking_federal']);
    }

    public function testEmptyExcelRefundFileGeneration()
    {
        $payments = $this->createPaymentsToClaim();

        $data = $this->generateRefundsExcelForNb($this->bank);

        // Refund file is never generated as count is 0
        $this->assertEquals(0, $data['netbanking_federal']['count']);
        $this->assertFalse(array_key_exists('file', $data));
    }

    protected function createPaymentsToClaim()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)
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

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)
                                                      ->addMinutes(45)
                                                      ->timestamp;

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
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
                        $date = Carbon::today('Asia/Kolkata')->format('d_m_Y');

                        $emails = ['settlements@razorpay.com'];

                        $testData = [
                            'file_path' => 'FBK_REFUND_' . $date . '.txt',
                            'subject'   => 'Federal Netbanking refunds file for ' . $date,
                            'emails'    => $emails
                        ];

                        $this->assertArraySelectiveEquals($testData, $data);

                        return true;
                    }),
                    Mockery::any()
                );
    }

    protected function checkRefundFileData($data)
    {
        //
        // Asserting that the file exists
        // Asserting that the total amount is 1000 rupees
        // asserting that the total number of refunds is 3
        //
        $this->assertTrue(file_exists($data['file'][1]));
        $this->assertEquals(100000, $data['file'][0]);
        $this->assertEquals(3, $data['count']);

        $refundsFileContents = file($data['file'][1]);

        // 3 refunds + 0 initial line
        assert(count($refundsFileContents) === 3);

        // Individual refund amounts to be asserted
        $refundAmounts = ['10000', '40000', '50000'];

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
            assert(in_array($rowRefundAmount, $refundAmounts));
        }

        unlink($data['file'][1]);
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = '||||';
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
}
