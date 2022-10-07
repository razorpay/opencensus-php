<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Gateway\Netbanking\Axis\ResponseFields;
use RZP\Gateway\Netbanking\Axis\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingAxisGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAxisGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_axis';

        $this->payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $gatewayEntity);

        $gatewayMerchantId = $this->terminal->getGatewayMerchantId();

        $this->assertEquals($gatewayMerchantId, $gatewayEntity['reference1']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['AMT'] = '1';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment($this->payment);
        });
    }

    public function testVerifyAmountMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockAmountMismatch();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });
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

        $this->assertEquals('Y', $gatewayEntity['status']);

        $gatewayMerchantId = $this->terminal->getGatewayMerchantId();

        $this->assertEquals($gatewayMerchantId, $gatewayEntity['reference1']);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    // Backward compatibility test
    public function testRetailPaymentWithCorpTerminalPresent()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');

        $this->testPayment();
    }

    // New expected flow
    public function testCorporatePayment()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->payment = $this->getDefaultNetbankingPaymentArray('UTIB_C');

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content['type'] = 'corporate';
                }
            });

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $testData = $this->testData['testPayment'];
        $testData['bank'] = 'UTIB_C';
        $testData['terminal_id'] = '100NbAxisCrpTl';

        $this->assertArraySelectiveEquals($testData, $payment);

        $testData = $this->testData['testPaymentNetbankingEntity'];
        $testData['bank'] = 'UTIB_C';

        $this->assertArraySelectiveEquals($testData, $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);
    }

    public function testCorporatePendingPayment()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->payment = $this->getDefaultNetbankingPaymentArray('UTIB_C');

        $this->server = $this->mockPendingResponse();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );

        $this->mockS2sCallForPaymentFromBank();

        // Entry refreshed with the actual payment
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $testData = $this->testData['testPaymentNetbankingEntity'];
        $testData['bank'] = 'UTIB_C';

        $this->assertArraySelectiveEquals($testData, $gatewayPayment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertEquals(9999999999, $gatewayPayment['bank_payment_id']);
    }

    public function testVerifyForCorporatePayments()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $this->payment = $this->getDefaultNetbankingPaymentArray('UTIB_C');

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content['type'] = 'corporate';
                }
            });

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content['type'] = 'corporate';
                }
            });

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        assert($payment['verified'] === 1);
    }

    public function testTpvVerifyPayment()
    {
        $this->testTpvPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSetBankPaymentId();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertEquals($gatewayEntity['reference1'], $verifyResponseContent['ITC']);

        $order = $this->getLastEntity('order', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertEquals($gatewayEntity['account_number'], $data['account_number']);

        $this->assertEquals('Y', $gatewayEntity['status']);

        $gatewayMerchantId = $this->terminal->getGatewayMerchantId();

        $this->assertEquals($gatewayMerchantId, $gatewayEntity['reference1']);

        $this->assertArraySelectiveEquals($data, $order);
    }

    public function testPaymentVerify()
    {

        $payment = $this->doAuthPayment($this->payment);

        $this->mockSetBankPaymentId();

        $verify = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment['reference1'], $verifyResponseContent['ITC']);

        $this->assertEquals('Y', $gatewayPayment['status']);

        $gatewayMerchantId = $this->terminal->getGatewayMerchantId();

        $this->assertEquals($gatewayMerchantId, $gatewayPayment['reference1']);
    }

    /**
     * This is to test that the payments before April 20th @ 2pm are verified
     * with the ITC parameter set as caps_payment_id
     */
    public function testOldPaymentVerify()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit(
            'payment',
            $payment['id'],
            [
                'created_at'    => 1481696800,
                'authorized_at' => 1481696810,
                'captured_at'   => 1481696820
            ]
        );

        $this->mockSetBankPaymentId();

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($verifyResponseContent['PAYEEID']);
        $this->assertNotNull($verifyResponseContent['PRN']);
        $this->assertEquals($gatewayPayment['caps_payment_id'], $verifyResponseContent['ITC']);

        $this->assertEquals('Y', $gatewayPayment['status']);
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

        $data = $this->generateRefundsExcelForNb('UTIB');

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

    public function testMulipleTableVerifyResponse()
    {

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockMultipleVerifyTables('F');

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);
    }

    public function testMulipleSuccessTableVerifyResponse()
    {
        $this->mockMultipleVerifyTables('S');

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('9999999999', $payment['acquirer_data']['bank_transaction_id']);

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals('9999999999', $netbankingEntity['bank_payment_id']);
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

        $data = $this->testData['testVerifyMismatch'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment);
    }

    // Auth fails but verify shows success
    public function testAuthFailedVerifySuccess()
    {

        $data = $this->testData[__FUNCTION__];

        $this->testFailedAuthPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockSetBankPaymentId();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals('Y', $gatewayPayment['status']);

        $gatewayMerchantId = $this->terminal->getGatewayMerchantId();

        $this->assertEquals($gatewayMerchantId, $gatewayPayment['reference1']);
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

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function checkMailQueue()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

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
        Mail::assertQueued(DailyFileMail::class, function ($mail) use ($testData)
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

        Mail::assertQueued(DailyFileMail::class, function ($mail) use ($testData)
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
                if ($action !== 'multiple_tables')
                {
                    $content['PAID'] = 'N';
                }
            });
    }

    protected function mockVerifyStatusFailure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action !== 'multiple_tables')
                {
                    $gatewayEntity = $this->getLastEntity('netbanking', true);

                    $content['BID'] = $gatewayEntity['bank_payment_id'];
                    $content['PaymentStatus'] = 'F';
                }
            });
    }

    protected function mockMultipleVerifyTables($status)
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($status)
            {
                if ($action === 'multiple_tables')
                {
                    $gatewayEntity = $this->getLastEntity('netbanking', true);

                    $content->Table1->BID = '11111111';

                    $response = (array) $content;
                    $array = json_decode(json_encode($response), true);

                    $table2 = array_flip($array['Table1']);

                    $content->addChild('Table2');
                    array_walk_recursive($table2, array ($content->Table2, 'addChild'));

                    $content->Table1->PaymentStatus = $status;
                    $content->Table2->BID = $gatewayEntity['bank_payment_id'];
                }
            });
    }

    protected function mockVerifyNullResponse()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify_content')
                {
                    $content = "";
                }
            });
    }

    protected function mockSetBankPaymentId()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action !== 'multiple_tables')
                {
                    $gatewayEntity = $this->getLastEntity('netbanking', true);

                    $content['BID'] = $gatewayEntity['bank_payment_id'];
                }
            });
    }

    protected function mockPendingResponse()
    {
        // Mocks pending response
        return $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content['type'] = 'corporate';
                }

                $content[ResponseFields::PAID] = Status::NO;
                $content[ResponseFields::FLAG] = Status::PENDING;
            });

    }

    protected function mockS2sCallForPaymentFromBank()
    {
        // Initial pending reponse based entry
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $content = $this->server->getS2sResponseForPayment($gatewayPayment);

        $request = [
            'content' => $content,
            'url' => '/callback/netbanking_axis',
            'method' => 'post'
        ];

        // Fire s2s callback request
        return $this->makeRequestAndGetContent($request);
    }

    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify_content')
            {
                $content[ResponseFields::STATUS]              = ResponseFields::PAYMENT_STATUS;
                $content[ResponseFields::VERIFY_RESPONSE_AMT] = '10.00';
            }
        });
    }

    public function testDetailedErrorResponseNetBanking()
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
}
