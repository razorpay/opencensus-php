<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Pnb;

use Mail;
use Mockery;
use Exception;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingPnbGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setup()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingPnbGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_pnb';

        $this->bank = 'PUNB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow($data, function()
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

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');
    }

    /**
     * Authorization fails, but verify shows success
     * Results in a payment verification error
     */
    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthorizeFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSetVerifyTransactionId();

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals($refund['amount'], 50000);
    }

    public function testRefundPartial()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $refund = $this->refundPayment($payment['id'], 10000);

        $this->assertEquals($refund['amount'], 10000);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
    }

    public function testRefundFailed()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $refund = $this->refundPayment($payment['id'], 100000);
            });
    }

    public function testPnbDailyFileGeneration()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $this->createRefundForFileGeneration($payments);

        $data = $this->generateRefundsExcelForNB('PUNB');

        $this->checkRefundTextData($data);

        $this->checkMailQueue();
    }

    public function testPnbDailyFileGenerationEmpty()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $data = $this->generateRefundsExcelForNb('PUNB');

        $this->checkEmptyRefundTextData($data);

        $this->checkEmptyRefundsMailQueue();
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['BankStatus'] = 'F';
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['bankstatus'] = 'F';
            }
        });
    }

    protected function mockSetVerifyTransactionId()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $gatewayPayment = $this->getLastEntity('netbanking', true);

            $content['txns'][0]['txnid'] = $gatewayPayment['bank_payment_id'];
        });
    }

    protected function createRefundForFileGeneration($payments)
    {
        // Refund a payment
        $lastPayment = $payments['items'][2];

        // Refunding 100 rupees followed by 400
        $this->refundPayment($lastPayment['id'], 10000);

        $this->refundPayment($lastPayment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function createPaymentsToClaim()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', ['count' => 3], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
                                                      ->addMinutes(30)
                                                      ->subDays(2)
                                                      ->timestamp;

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                                                              'authorized_at' => $createdAt,
                                                              'captured_at'   => $createdAt]);
        }

        $p1 = $this->doAuthAndCapturePayment($this->payment);

        $p2 = $this->doAuthAndCapturePayment($this->payment);

        $payments1 = $this->getEntities('payment', ['count' => 2], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)
                                                      ->addMinutes(30)
                                                      ->timestamp;

        foreach ($payments1['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                                                              'authorized_at' => $createdAt + 10,
                                                              'captured_at'   => $createdAt + 20]);
        }

        $payments['items'] = array_merge($payments1['items'], $payments['items']);

        $payments['count'] = count($payments['items']);

        return $payments;
    }

    protected function checkRefundTextData($data)
    {
        $this->assertTrue(file_exists($data['netbanking_pnb']['refunds']));

        $this->assertTrue(file_exists($data['netbanking_pnb']['claims']));

        $refundsFileContents = file($data['netbanking_pnb']['refunds']);

        $claimsFileContents = file($data['netbanking_pnb']['claims']);

        $refundsFilePath = explode('/', $data['netbanking_pnb']['refunds']);

        $claimsFilePath = explode('/', $data['netbanking_pnb']['claims']);

        $refundsFileName = end($refundsFilePath);

        $claimsFileName = end($claimsFilePath);

        $time = Carbon::now(Timezone::IST);

        $this->assertEquals($refundsFileName, 'refund_PNB_NB_'. $time->format('Ymd') . '_V1_test.txt');

        $this->assertEquals($claimsFileName, 'PNB_Netbanking_Claims_test_'. $time->format('d-m-Y') .  '.txt');

        assert(count($refundsFileContents) === 2);

        assert(count($claimsFileContents) === 4);

        $refundsFileContentLine = explode('|', $refundsFileContents[0]);

        assert(count($refundsFileContentLine), 7);
    }


    protected function checkEmptyRefundTextData($data)
    {
        $this->assertTrue(file_exists($data['netbanking_pnb']['refunds']) === false);

        $this->assertTrue(file_exists($data['netbanking_pnb']['claims']));

        $claimsFileContents = file($data['netbanking_pnb']['claims']);

        // 3 claims
        assert(count($claimsFileContents) === 2);
    }

    protected function checkMailQueue()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        // Amounts are in rupees
        $testData = [
            'subject' => 'Pnb Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 500.0,
                    'refunds' => 500.0,
                ],
                'count'   => [
                    'claims'  => 4,
                    'refunds' => 2,
                    'total'   => 6
                ]
        ];

        // Mail catch with amount and refund everywhere
        Mail::assertSent(DailyFileMail::class, function ($mail) use ($testData)
        {
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function checkEmptyRefundsMailQueue()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        // Amounts are in rupees
        $testData = [
            'subject' => 'Pnb Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 1000.0,
                    'refunds' => 0,
                ],
                'count'   => [
                    'claims'  => 2,
                    'refunds' => 0,
                    'total'   => 2
                ]
        ];

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($testData)
        {
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }
}
