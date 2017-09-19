<?php

namespace RZP\Tests\Functional\Gateway\Hdfc;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class HdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/HdfcGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'hdfc';

        $this->setMockGatewayTrue();

        $this->mockTokenex();

        $this->fixtures->merchant->enableInternational();

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');
    }

    public function testPayment()
    {
        $payment = $this->defaultAuthPayment();

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcPaymentEntity'], $payment);
    }

    public function testTamperedPayment()
    {
        $payment = $this->doAuthPayment();

        $id = Payment::verifyIdAndSilentlyStripSign($payment['razorpay_payment_id']);

        $this->mockServerContentFunction(function (& $content, $action) use ($id)
        {
            if ($action === 'authorize')
            {
                $content['trackid'] = $id;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card'] = [
                'name' => 'card holder',
                'number' => '4012001037167778',
                'expiry_month' => 1,
                'expiry_year' => 2099,
                'cvv' => '123'
            ];

            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPayment()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];
        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        // $this->assertTestResponse($paymentEntity);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('FssRecurringTl', $paymentEntity['terminal_id']);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($paymentId);

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertNotNull($hdfc['ref']);
        $this->assertNotNull($hdfc['auth']);
        $this->assertEquals($paymentId, $hdfc['payment_id']);
        $this->assertEquals('APPROVED', $hdfc['result']);
        $this->assertEquals('authorized', $hdfc['status']);

        $payment = $this->capturePayment($paymentEntity['id'], $paymentEntity['amount']);

        $hdfcCaptured = $this->getLastEntity('hdfc', true);

        $hdfcData = $this->testData['testHdfcPaymentEntity'];

        $this->assertArraySelectiveEquals($hdfcData, $hdfcCaptured);
    }

    public function testInternationalUSDPaymentOnApi()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $input = [
            'amount'   => 5000,
            'currency' => 'USD'
        ];

        $payment = $this->defaultAuthPayment($input);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['transaction_id'], null);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount'], 'USD');

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hdfc', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHdfcUSDPaymentEntity'], $gatewayPayment);

        $this->refundPayment($payment['id'], $payment['amount'] / 2);
    }

    public function testMaestroCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5081597022059105';

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);
        $this->assertNotNull($payment['transaction_id']);

        $this->assertEquals(TwoFactorAuth::PASSED, $payment['two_factor_auth']);
    }

    public function testTwoFaNotApplicable()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012001037411127';

        $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(TwoFactorAuth::NOT_APPLICABLE, $payment['two_factor_auth']);
    }

    public function testRupayCard()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6073849700004947';

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals($payment['two_factor_auth'], 'passed');

        $this->verifyPayment($payment['id']);
        $this->capturePayment($payment['id'], $payment['amount']);
        $this->refundPayment($payment['id']);
    }

    public function testHdfcEntityAfterPaymentRefund()
    {
        $payment = $this->doAuthAndCapturePayment();

        $refund = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('hdfc', true);//sd($refund);
        $this->assertTestResponse($refund);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->defaultAuthPayment();

        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['id'], $input);

        $hdfcEntity = $this->getLastEntity('hdfc', true);
        $this->assertEquals('authorized', $hdfcEntity['status']);
        $this->assertEquals('APPROVED', $hdfcEntity['result']);

        $txn = $this->getLastTransaction(true);
        $this->assertNull($txn);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment();

        $this->verifyPayment($payment['razorpay_payment_id']);
    }

    public function testRupayPaymentAuthError()
    {
        $testData = [
            'response' => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\GatewayErrorException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
            ],
        ];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultPaymentArray();
            $payment['card']['number'] = '6073840000000008';

            $this->doAuthPayment($payment);
        });
    }

    public function testAuthorizeFailedPayment()
    {
        $this->timeoutHdfcAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('failed', $payment['status']);

        $this->succeedPaymentVerify();

        $this->authorizeFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $payment = $this->getLastEntity('hdfc', true);
    }

    public function testRefundDeniedByRisk()
    {
        $payment = $this->doAuthAndCapturePayment();

        $this->hdfcPaymentFailedDueToDeniedByRisk();

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->refundPayment($payment['id']);
        });

        $hdfc = $this->getLastEntity('hdfc', true);
        $this->assertTestResponse($hdfc);

        $payment = $this->getLastPayment();
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testPaymentFailWithFailureResultCode()
    {
        $this->hdfcPaymentMockResultCode('FAILURE(DENIED BY RISK)', 'authorize');

        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->doAuthPayment();
            });

        $hdfc = $this->getLastEntity('hdfc', true);

        $this->assertEquals($hdfc['result'], 'DENIED BY RISK');
    }

    protected function timeoutHdfcAuthorizePayment()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                throw new Exception\GatewayTimeoutException('Timed out');
            }

            return $content;
        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $content = $this->doAuthPayment();
            });
    }

    protected function succeedPaymentVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['RESPCODE'] = '0';
            $content['RESPMSG'] = 'Transaction succeeded';
            $content['STATUS'] = 'TXN_SUCCESS';

            return $content;
        });
    }

    protected function authErrorOnRupayPayment()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['amt'] = '1.0';
            $content['result'] = 'AUTH ERROR';
            unset($content['PAReq'], $content['eci']);
            return $content;
        });
    }
}
