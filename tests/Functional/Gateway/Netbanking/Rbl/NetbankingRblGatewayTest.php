<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Rbl;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingRblGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingRblGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_rbl';

        $this->bank = 'RATN';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->payment['amount'] = 10000012;

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

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

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');

        $this->assertEquals($gatewayPayment['bank_payment_id'], $payment['reference1']);
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

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

    public function testTpvPaymentForAccountNumPreseedingWith0()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest($data);

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals($this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals('0403040304', $gatewayEntity['account_number']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
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

    public function testVerifyAmountMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = simplexml_load_string($content);

                $content->RetrieveTransactionStatus->RetrieveTransactionStatus_REC->ENTRY_AMOUNT_ARRAY = 'INR|5,00,000.00';

                $content = $content->asXml();
            }
        });

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });
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

        $payments = $this->createPayments();

        $this->setPaymentsReconciledAtToday();

        $this->createRefundsForFileGeneration($payments);

        $data = $this->generateRefundsExcelForNb($this->bank);

        $this->assertTrue(file_exists($data['netbanking_rbl']['refunds']));

        $this->assertTrue(file_exists($data['netbanking_rbl']['claims']));

        unlink($data['netbanking_rbl']['refunds']);

        unlink($data['netbanking_rbl']['claims']);

        $this->checkMailQueue();
    }

    protected function setPaymentsReconciledAtToday()
    {
        // Set the transactions to be reconciled today
        $transactions = $this->getEntities('transaction', [], true);

        $reconciledAt = Carbon::today(Timezone::IST)->addHours(5)->addMinutes(13)->timestamp;

        foreach ($transactions['items'] as $transaction)
        {
            $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $reconciledAt]);
        }
    }

    protected function createPayments()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $this->doAuthAndCapturePayment($this->payment);

        $payments = $this->getEntities('payment', [], true);

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
        Mail::assertQueued(DailyFileMail::class, function ($mail)
        {
            $this->assertEquals('3', $mail->viewData['count']['refunds']);

            $this->assertEquals('3', $mail->viewData['count']['claims']);

            return true;
        });
    }

    protected function mockFailedVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = simplexml_load_string($content);

                $content->RetrieveTransactionStatus->RetrieveTransactionStatus_REC->ENTRY_STATUS = 'FAL';

                $content = $content->asXml();
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['STATUS'] = 'FAL';
            }
        });
    }
}
