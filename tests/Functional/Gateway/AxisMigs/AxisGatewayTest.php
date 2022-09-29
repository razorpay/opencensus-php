<?php

namespace RZP\Tests\Functional\Gateway\AxisMigs;

use Mockery;
use Mail;
use Carbon\Carbon;

use RZP\Mail\Payment\FailedToAuthorized as FailedToAuthorizedMail;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Fixtures;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Error;
use RZP\Error\PublicErrorCode;
use RZP\Models\Terminal\Options as TerminalOptions;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['refund_at']);
        $this->assertSame('authorized', $payment['status']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);

        $payment = $this->getDbLastEntityPublic('payment');

        $this->assertNull($payment['transaction_id']);
        $this->assertEquals(TwoFactorAuth::PASSED, $payment[Entity::TWO_FACTOR_AUTH]);

        $migs = $this->getDbEntity('axis_migs', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $migs);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySubset([
            'refund_at' => null,
            'status'    => 'captured',
        ], $payment);

        $txn = $this->getDbLastEntityPublic('transaction');

        $this->assertArraySelectiveEquals($this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $migs = $this->getDbLastEntityPublic('axis_migs');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsCaptureEntity'], $migs);
    }

    public function testPaymentForAuthorizationTerminal()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                if ($feature === 'save_all_cards' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                                {
                                    return 'off';
                                }
                                return 'on';
                            }));

        TerminalOptions::setTestChance(200);

        $this->createGatewayRules($this->testData[__FUNCTION__]);

        $payment = [
            'card' => [
                'number'       => '5567630000002004',
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ];

        $this->fixtures->create('terminal:shared_axis_terminal', ['id' => '1001AxisMigsTl', 'capability' => 2]);

        $this->fixtures->terminal->disableTerminal('1000AxisMigsTl');

        $this->mockCardVault();
        $payment = $this->defaultAuthPayment($payment);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);

        $gatewayPayment = $this->getLastEntity('axis_migs', true);

        $this->assertEquals('capture', $gatewayPayment['action']);
        $this->assertEquals('Approved', $gatewayPayment['vpc_Message']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('mpi_blade', $mpi['gateway']);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['vpc_Amount'] = '100';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $this->doAuthPayment();
        });
    }

    public function testMasterCardPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthPayment($payment);
    }

    public function testFailedPayment()
    {
        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->doAuthAndCapturePayment();
        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($amount, $refund['vpc_amount']);
    }

    public function testAuthorizedPaymentRefund()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];
        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);

        $this->assertEquals(true, $refund['gateway_refunded']);

        $this->assertNull($refund['transaction_id']);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertEquals('voidAuthorisation', $migs['vpc_Command']);
    }

    public function testMaestroOnMigsFailOnLive()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $this->fixtures->on('live')->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->edit('10000000000000',
                                        [
                                            'activated' => 1,
                                            'live' => 1,
                                            'pricing_plan_id' => '1hDYlICobzOCYt'
                                        ]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->ba->publicLiveAuth();
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment();
        $this->assertEquals($payment['status'], 'captured');

        $this->verifyPayment($payment['id']);
        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertEquals('capture', $payment['vpc_Command']);
    }

    public function testPaymentVerifyFailed()
    {
        $payment = $this->doAuthPayment();
        $pid = $payment['razorpay_payment_id'];

        $this->fixtures->payment->edit($pid, ['status' => 'failed', 'authorized_at' => null]);

        $this->mockServerContentFunction(function (& $content)
        {
            $content['vpc_DRExists'] = 'Y';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($pid)
        {
            $this->verifyPayment($pid);
        });
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['vpc_Message']         = 'I5426-07060432: Invalid Permission : advanceMA';
                $content['vpc_TransactionNo']   = '0';
                $content['vpc_TxnResponseCode'] = '7';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['vpc_RefundedAmount'] = $content['vpc_Amount'];
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $payment['id']);

        $this->assertEquals($refund['id'], $response['refund_id']);

        $this->assertEquals('created', $response['status']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['vpc_Message']         = 'I5426-07060432: Invalid Permission : advanceMA';
                $content['vpc_TransactionNo']   = '0';
                $content['vpc_TxnResponseCode'] = '7';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                unset($content['vpc_AcqResponseCode'], $content['vpc_Card'], $content['vpc_MerchTxnRef']);
                unset($content['vpc_Message'], $content['vpc_ReceiptNo'], $content['vpc_TxnResponseCode']);

                $content['vpc_Amount'] = '0';
                $content['vpc_BatchNo'] = '0';
                $content['vpc_DRExists'] = 'N';
                $content['vpc_FoundMultipleDRs'] = 'N';
                $content['vpc_TransactionNo'] = '0';
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('created', $response['status']);
    }

    public function testVerifyRefundFailedOnGatewayMultipleResponses()
    {
        $this->markTestSkipped('the expected exceptions will not be caught through the scrooge flow');

        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['vpc_Message']         = 'I5426-07060432: Invalid Permission : advanceMA';
                $content['vpc_TransactionNo']   = '0';
                $content['vpc_TxnResponseCode'] = '7';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['vpc_FoundMultipleDRs'] = 'Y';
                $content['vpc_RefundedAmount'] = $content['vpc_Amount'];
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($refund)
        {
            $this->retryFailedRefund($refund['id'], $refund['payment_id']);
        });
    }

    public function testVerifyRefundOldRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $ts = Carbon::createFromDate(2017, 1, 1)->getTimestamp();

        $this->fixtures->refund->edit($refund['id'], [
            'status' => 'failed',
            'created_at' => $ts,
            'gateway_refunded' => '0'
        ]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($ts, $refund['created_at']);
        $this->assertFalse($refund['gateway_refunded']);
        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($refund)
        {
            $this->retryFailedRefund($refund['id']);
        });
    }

    public function testVerifyRefundOldRefundScrooge()
    {
        $payment = $this->doAuthAndCapturePayment();

        $ts = Carbon::createFromDate(2017, 1, 1)->getTimestamp();

        $data = [
            'status' => 'created',
            'created_at' => $ts,
        ];

        $this->refundPayment($payment['id'], $payment['amount'], $data);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testInvalidAmaCaptureError()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'capture')
            {
                unset($content['vpc_AcqResponseCode'], $content['vpc_AuthorisedAmount'],
                      $content['vpc_CapturedAmount'], $content['vpc_Card'],
                      $content['vpc_ReceiptNo'], $content['vpc_ShopTransactionNo']);

                $content['vpc_Amount']          = '0';
                $content['vpc_BatchNo']         = '0';
                $content['vpc_Currency']        = 'INR';
                $content['vpc_Message']         = 'I5426-07060432: Invalid Permission : advanceMA';
                $content['vpc_TransactionNo']   = '0';
                $content['vpc_TxnResponseCode'] = '7';
            }
        });

        $testData = $this->testData['testInvalidAmaCaptureError'];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthAndCapturePayment($testData['request']['content']);
        });
    }

    public function testCaptureError()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'capture')
            {
                unset($content['vpc_AcqResponseCode'], $content['vpc_AuthorisedAmount'],
                      $content['vpc_CapturedAmount'], $content['vpc_Card'],
                      $content['vpc_ReceiptNo'], $content['vpc_ShopTransactionNo']);

                $content['vpc_Amount']          = '0';
                $content['vpc_BatchNo']         = '0';
                $content['vpc_Currency']        = 'INR';
                // @codingStandardsIgnoreLine
                $content['vpc_Message']         = 'E5414-08311437: Capture Error : Field in error: \'transaction.amount\', value \'INR 500.00\' - reason: Requested capture amount exceeds outstanding authorized amount';
                $content['vpc_TransactionNo']   = '0';
                $content['vpc_TxnResponseCode'] = '7';
            }
        });

        $testData = $this->testData['testCaptureError'];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthAndCapturePayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentWithProperError()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSRequestCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '91';
                $content['vpc_CSCResultCode']     = 'N';
                $content['vpc_Message']           = 'Timed out';
                $content['vpc_TxnResponseCode']   = '3';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData['testFailedPaymentWithProperError'];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testAuthorizeFailedPayment()
    {
        Mail::fake();

        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->resetMockServer();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');

        Mail::assertQueued(FailedToAuthorizedMail::class);
    }

    public function testForceAuthorizePayment()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $payment = $this->doAuthPayment();
        $migs = $this->getDbLastEntityPublic('axis_migs');
        $txnNo = (int) $migs['vpc_TransactionNo'] - 1;

        $this->failAuthorizePayment();

        $payment = $this->getDbLastEntityPublic('axis_migs');
        $pid1 = 'pay_'.$payment['payment_id'];
        $txnNoNew = $payment['vpc_TransactionNo'];

        $this->fixtures->edit('axis_migs', $payment['id'], ['received' => '0']);

        $this->resetMockServer();

        $content = $this->forceAuthorizeFailedPayment($pid1, ['vpc_TransactionNo' => $txnNo]);

        $payment = $this->getDbLastEntityPublic('payment');

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getDbLastEntityPublic('axis_migs');

        $this->assertEquals($payment['vpc_TransactionNo'], $txnNoNew);
    }

    // @codingStandardsIgnoreLine
    public function testFailureWhen3DSFailsForDomesticMerchant()
    {
        $this->fixtures->merchant->disableInternational();

        $testData = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '55553555655655';

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $payment = $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(TwoFactorAuth::FAILED, $payment[Entity::TWO_FACTOR_AUTH]);

        $this->assertEquals($payment['status'], 'failed');
    }

    // @codingStandardsIgnoreLine
    public function testFailureWhen3DSFailsForRiskyMerchant()
    {
        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->enableRisky();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '55553555655655';
            $payment = $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentAuthenticateCard()
    {
        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity, __FUNCTION__ . 'Data');
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('MiGSRcgTmnl3DS', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($paymentId, 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity, __FUNCTION__ . 'Data');
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('MiGSRcgTmlN3DS', $paymentEntity['terminal_id']);

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $migs = $this->getDbEntity('axis_migs', ['action' => 'authorize'])->toArrayAdmin();

        $migsData = $this->testData['recurringEntity'];

        $this->assertNotNull($migs['vpc_TransactionNo']);
        $this->assertNotNull($migs['vpc_AuthorizeId']);
        $this->assertEquals($paymentId, $migs['payment_id']);
        $this->assertArraySelectiveEquals($migsData, $migs);
    }

    public function testFailedPaymentMessageException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSRequestCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '91';
                $content['vpc_CSCResultCode']     = 'N';
                $content['vpc_Message']           = 'E5415-09120704: Refund Error :
                                                     Field in error: \'initialTransaction.orderNumber\',
                                                     value \'27950\' - reason: No order identified';
                $content['vpc_TxnResponseCode']   = '3';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentAcqResponseCodeException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSRequestCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '91';
                $content['vpc_CSCResultCode']     = 'N';
                $content['vpc_Message']           = '';
                $content['vpc_TxnResponseCode']   = 'E';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentTxnResponseCodeException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSRequestCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '';
                $content['vpc_CSCResultCode']     = 'N';
                $content['vpc_Message']           = '';
                $content['vpc_TxnResponseCode']   = 'E';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentAvsResponseCodeException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSResultCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '';
                $content['vpc_CSCResultCode']     = 'N';
                $content['vpc_Message']           = '';
                $content['vpc_TxnResponseCode']   = '';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentCscResponseCodeException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSResultCode']    = '';
                $content['vpc_AcqCSCRespCode']    = 'N';
                $content['vpc_AcqResponseCode']   = '';
                $content['vpc_CSCResultCode']     = 'U';
                $content['vpc_Message']           = '';
                $content['vpc_TxnResponseCode']   = '';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testFailedPaymentUnknownResponseCodeException()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'acs')
            {
                $content['vpc_3DSECI']            = '05';
                $content['vpc_AVSRequestCode']    = 'Z';
                $content['vpc_AcqCSCRespCode']    = '';
                $content['vpc_AcqResponseCode']   = '';
                $content['vpc_CSCResultCode']     = 'AVNA';
                $content['vpc_Message']           = '';
                $content['vpc_TxnResponseCode']   = '';
                $content['vpc_VerSecurityLevel']  = '05';
                $content['vpc_VerStatus']         = 'Y';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->replaceDefaultValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData, function () use ($testData)
        {
            $this->doAuthPayment($testData['request']['content']);
        });
    }

    public function testPaymentRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'refund')
            {
                $content['vpc_TxnResponseCode'] = '7';
                $content['vpc_Message'] = 'E5414-09281349 reason: Requested capture amount exceeds outstanding authorized amount';
            }
        });

        $refund = $this->refundPayment($payment['id']);
        $response = $this->scroogeRefund($refund);

        $this->assertEquals($response['success'], false);

        $this->assertEquals($response['status_code'], ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH);
    }

    public function testPaymentRefundWithCardTransfer()
    {
        $payment = $this->doAuthAndCapturePayment();

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);
        $this->fixtures->merchant->editDefaultRefundSpeed('optimum');

        $refund = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true, 'fta_data' => ['card_transfer' => ['card_id' => $payment['card_id']]]]);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        $refund = $this->getLastEntity('refund', true);

        // Refund will be in created state
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals('optimum', $refund['speed_requested']);
    }

    public function testPaymentRefundWithCardTransferFailed()
    {
        $payment = $this->doAuthAndCapturePayment();

        $card = $this->getDbLastEntity('card');

        $this->fixtures->iin->edit($card['iin'], ['type' => 'debit']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $refund = $this->refundPayment($payment['id']);

        // Assert for fta not created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNull($fta);

        $refund = $this->getLastEntity('refund', true);

        // Refund will be in processed state
        $this->assertEquals($refund['status'], 'processed');
    }

    protected function createGatewayRules($rules)
    {
        foreach ($rules as $rule)
        {
           $this->fixtures->create('gateway_rule', $rule);
        }
    }
}
