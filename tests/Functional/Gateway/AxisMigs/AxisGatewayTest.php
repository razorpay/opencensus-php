<?php

namespace RZP\Tests\Functional\Gateway\AxisMigs;

use Mockery;
use Mail;
use Carbon\Carbon;

use RZP\Mail\Payment\FailedToAuthorized as FailedToAuthorizedMail;
use RZP\Models\Payment;
use RZP\Tests\Functional\Fixtures;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Error;
use RZP\Error\PublicErrorCode;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['transaction_id']);
        $this->assertEquals(TwoFactorAuth::PASSED, $payment[Entity::TWO_FACTOR_AUTH]);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsEntity'], $migs);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $migs = $this->getLastEntity('axis_migs', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentAxisMigsCaptureEntity'], $migs);
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

    public function testPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('axis_migs', true);

        $this->assertTestResponse($refund);
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
        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];
        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);
        // $this->assertTestResponse($refund);

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
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1, 'live' => 1, 'pricing_plan_id' => '1hDYlICobzOCYt']);
        // $merchant = $this->fixtures->merchant->activate();

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

        Mail::assertSent(FailedToAuthorizedMail::class);
    }

    public function testForceAuthorizePayment()
    {
        $payment = $this->doAuthPayment();
        $migs = $this->getLastEntity('axis_migs', true);
        $txnNo = (int) $migs['vpc_TransactionNo'] - 1;

        $this->failAuthorizePayment();

        $payment = $this->getLastEntity('axis_migs', true);
        $pid1 = 'pay_'.$payment['payment_id'];
        $txnNoNew = $payment['vpc_TransactionNo'];

        $this->fixtures->edit('axis_migs', $payment['id'], ['received' => '0']);

        $this->resetMockServer();

        $this->forceAuthorizeFailedPayment($pid1, ['vpc_TransactionNo' => $txnNo]);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('axis_migs', true);

        $this->assertEquals($payment['vpc_TransactionNo'], $txnNoNew);
    }

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
        $this->mockTokenex();

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
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

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('MiGSRcgTmlN3DS', $paymentEntity['terminal_id']);

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $migs = $this->getLastEntity('axis_migs', true);

        $migsData = $this->testData['recurringEntity'];

        $this->assertNotNull($migs['vpc_TransactionNo']);
        $this->assertNotNull($migs['vpc_AuthorizeId']);
        $this->assertEquals($paymentId, $migs['payment_id']);
        $this->assertArraySelectiveEquals($migsData, $migs);
    }
}
