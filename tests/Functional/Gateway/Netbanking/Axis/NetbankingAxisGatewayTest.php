<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Mail\Gateway\DailyFile as DailyFileMail;
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

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);
    }

    public function testTpvPayment()
    {
        $this->fixtures->create('terminal:shared_netbanking_axis_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $order = $this->getLastEntity('order');

        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], '100NbAxisTpvTl');

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

        $this->assertEquals($gatewayEntity['status'], 'Y');

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testTpvVerifyPayment()
    {
        $this->testTpvPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSetBankPaymentId();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $order = $this->getLastEntity('order', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertEquals($gatewayEntity['account_number'], $data['account_number']);

        $this->assertEquals($gatewayEntity['status'], 'Y');

        $this->assertArraySelectiveEquals($data, $order);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment($this->payment);

        $this->mockSetBankPaymentId();

        $content = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($content['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment['status'], 'Y');
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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_refunded'], 10000);
    }

    public function testFailedRefund()
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

    public function testDailyFileGeneration()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $this->createRefundForFileGeneration($payments);

        $data = $this->generateRefundsExcelForNB('UTIB');

        // @note: Commented during move to 5.4
        // $this->checkMailQueue();

        $this->checkRefundTextData($data);

        $this->checkMailQueue();
    }

    public function testEmptyDailyFileGeneration()
    {
        Mail::fake();

        $payments = $this->createPaymentsToClaim();

        $data = $this->generateRefundsExcelForNb('UTIB');

        $this->checkEmptyRefundTextData($data);

        $this->checkEmptyRefundsMailQueue();
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

        $this->mockVerifyStatusFailure();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['razorpay_payment_id']);
            });
    }

    /**
     * In some cases when authorize was a failure,
     * verify returns a null response
     * We expect a status_match
     */
    public function testAuthFailedVerifyNullResponse()
    {
        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockVerifyNullResponse();

        $data = $this->testData[__FUNCTION__];

        $verify = $this->verifyPayment($payment['id']);

        $this->assertArraySelectiveEquals($data, $verify);
    }

    // Auth fails but verify shows success
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

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals('Y', $gatewayPayment['status']);
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

        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at'    => $createdAt,
                                                              'authorized_at' => $createdAt + 10,
                                                              'captured_at'   => $createdAt + 20]);
        }

        return $payments;
    }

    protected function createRefundForFileGeneration($payments)
    {
        // Refund a payment
        $lastPayment = $payments['items'][2];

        // Refunding 100 rupees followed by 400
        $this->refundPayment($lastPayment['id'], 10000);

        $this->refundPayment($lastPayment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkMailQueue()
    {
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y');

        // Amounts are in rupees
        $testData = [
            'subject' => 'Axis Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 1500,
                    'refunds' => 500,
                    'total'   => 1000,
                ],
                'count'   => [
                    'claims'  => 3,
                    'refunds' => 2,
                    'total'   => 5
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
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y');

        // Amounts are in rupees
        $testData = [
            'subject' => 'Axis Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 1500,
                    'refunds' => 0,
                    'total'   => 1500,
                ],
                'count'   => [
                    'claims'  => 3,
                    'refunds' => 0,
                    'total'   => 3
                ]
        ];

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($testData)
        {
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function checkRefundTextData($data)
    {
        $this->assertTrue(file_exists($data['netbanking_axis']['refunds']));

        $this->assertTrue(file_exists($data['netbanking_axis']['claims']));

        $refundsFileContents = file($data['netbanking_axis']['refunds']);

        $claimsFileContents = file($data['netbanking_axis']['claims']);

        // 2 refunds + 1 initial line
        assert(count($refundsFileContents) === 3);

        // 3 claims + 1 initial line
        assert(count($claimsFileContents) === 4);

        $refundsFileLine1 = explode('~~', $refundsFileContents[1]);

        // Each line should have 8 columns
        assert(count($refundsFileLine1) === 8);

        $claimsFileLine1 = explode('~~', $claimsFileContents[1]);

        // Each line should have 7 columns
        assert(count($claimsFileLine1) === 7);
    }

    protected function checkEmptyRefundTextData($data)
    {
        $this->assertTrue(file_exists($data['netbanking_axis']['refunds']) === false);

        $this->assertTrue(file_exists($data['netbanking_axis']['claims']));

        $claimsFileContents = file($data['netbanking_axis']['claims']);

        // 3 claims + 1 initial line
        assert(count($claimsFileContents) === 4);

        $claimsFileLine1 = explode('~~', $claimsFileContents[1]);

        // Each line should have 7 columns
        assert(count($claimsFileLine1) === 7);
    }

    protected function mockPaymentFailure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content['PAID'] = 'N';
            });
    }

    protected function mockVerifyStatusFailure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content['PaymentStatus'] = 'F';
            });
    }

    protected function mockVerifyNullResponse()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content = "";
            });
    }

    protected function mockSetBankPaymentId()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $gatewayEntity = $this->getLastEntity('netbanking', true);

                $content['BID'] = $gatewayEntity['bank_payment_id'];
            });
    }
}
