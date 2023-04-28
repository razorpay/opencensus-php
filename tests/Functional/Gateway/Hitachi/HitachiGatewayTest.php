<?php

namespace RZP\Tests\Functional\Gateway\Hitachi;

use App;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Gateway\Hitachi;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Gateway;
use RZP\Services\DowntimeMetric;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Gateway\Hitachi\ResponseFields;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Exception\PaymentVerificationException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HitachiGatewayTest extends TestCase
{
    use PaymentTrait;
    use dbEntityFetchTrait;

    protected $razorX;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/HitachiGatewayTestData.php';

        parent::setUp();

        $this->otpFlow = false;

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' =>
                [
                    'non_recurring' => '1',
                    'recurring_3ds' => '1',
                    'recurring_non_3ds' => '1'
                ]
            ]);

        //
        // Hitachi is a lower priority card gateway than hdfc in
        // RZP\Models\Gateway\Priority\Defaults in the Method::Card section.
        // Therefore, we must disable hdfc terminal for the test cases
        // to run via the shared Hitachi terminal.
        //
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'hitachi';

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->mockCardVault();

        $this->mockShield();

        $this->mockMaxmind();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->createRiskConfig();

        $this->app->razorx
             ->method('getTreatment')
             ->will($this->returnCallback(function ($mid, $feature, $mode)
                {
                    if ($feature === 'shield_risk_evaluation')
                    {
                        return 'shield_on';
                    }

                    if ($feature === 'save_all_cards')
                    {
                        return 'off';
                    }

                    if ($feature === 'disable_rzp_tokenised_payment')
                    {
                        return 'off';
                    }

                    if ($feature === 'secure_3d_international')
                    {
                        return 'v2';
                    }

                    if ($feature === 'recurring_card_not_enabled')
                    {
                        return 'control';
                    }

                    if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1)
                    {
                        return 'off';
                    }

                    if ($feature === RazorxTreatment::NON_MERCHANT_REFUND_CREATE_V_1_1)
                    {
                        return 'off';
                    }

                    if ($feature === 'recurring_tokenisation_unhappy_flow_handling')
                    {
                        return 'control';
                    }
                    if ($feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }

                    if ($feature === 'payment_gateway_capture_async_other_networks')
                    {
                        return 'control';
                    }

                    if ($feature === 'payment_gateway_capture_asyc_mc')
                    {
                        return 'control';
                    }

                    return 'on';
                }));

    }

    public function testSuccessful13DigitPanForEnrolledCard()
    {
        $this->assertEquals([], $this->app['gateway_downtime_metric']->getMetrics());

        $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $this->assertEquals([
            $this->gateway => [
                DowntimeMetric::Success    => [
                    DowntimeMetric::NoError      => 1,
                ]
            ]
        ], $this->app['gateway_downtime_metric']->getMetrics());

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);
    }

    public function testRecurringPayment()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = CardNumber::VALID_VISA_NOT_ENROLLED;

        $this->fixtures->iin->create([
            'iin'       => '402400',
            'country'   => 'IN',
            'network'   => 'Visa',
            'issuer'    => 'KKBK',
            'recurring' => 1
        ]);
        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('100HitachiTmnl', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $this->assertEquals('02', $request['pECI']);
                $this->assertFalse(isset($request['pCVV2']));
            });

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($token, $paymentEntity['token_id']);
        $this->assertEquals('100HitachiTmnl', $paymentEntity['terminal_id']);
    }

    public function testRecurringPaymentForMaster()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = CardNumber::VALID_NOT_ENROLL_NUMBER;

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $this->assertNotNull($request['pMCProtocolVersion']);
            });

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('100HitachiTmnl', $paymentEntity['terminal_id']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $this->mockServerRequestFunction(
            function(& $request)
            {
                $this->assertEquals('07', $request['pECI']);
                $this->assertFalse(isset($request['pCVV2']));
            });

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($token, $paymentEntity['token_id']);
        $this->assertEquals('100HitachiTmnl', $paymentEntity['terminal_id']);
    }

    public function testNotEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_NOT_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);
    }

    //Authorize success for risky payment because VeRes and PaRes as 'Y'
    public function testInternationalRiskyPaymentSuccess()
    {
        $this->mockShield();

        $this->fixtures->iin->create([
            'iin'     => '514906',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::INTERNATIONAL_VISA_ENROLLED,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);
        $this->assertEquals('shield_v2', $paymentAnalytic['risk_engine']);
        $this->assertEquals('35', $paymentAnalytic['risk_score']);

        $txn = $this->getEntities('transaction', [], true);

        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);
    }

    //Failure due to shield response as block
    public function testPaymentFailureShieldBlock()
    {
        $this->mockShield();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment['card'] = [
            'number'       => '4012010000000007',
            'expiry_month' => '02',
            'expiry_year'  => '35',
            'cvv'          => 123,
            'name'         => 'Test Card'
        ];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals('PAYMENT_CONFIRMED_FRAUD_BY_SHIELD', $riskEntity['reason']);

        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals('payment_analytics', $paymentAnalytic['entity']);

        $this->assertEquals('shield_v2', $paymentAnalytic['risk_engine']);
    }

    //PaymentFailure due to risk validation for Veres as 'N'
    public function testNotEnrolledInternationalCard()
    {
        $this->mockShield();

        $this->fixtures->iin->create([
            'iin'     => '514906',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = [
            'number'       => CardNumber::INTERNATIONAL_VISA_NE,
            'expiry_month' => '02',
            'expiry_year'  => '35',
            'cvv'          => 123,
            'name'         => 'Test Card'
        ];

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $riskEntity = $this->getLastEntity('risk', true);

        $this->assertEquals($payment['id'], $riskEntity['payment_id']);

        $this->assertEquals('PAYMENT_FAILED_RISK_CHECK_IN_GATEWAY', $riskEntity['reason']);

        $paymentAnalytic = $this->getLastEntity('payment_analytics', true);

        $this->assertEquals('payment_analytics', $paymentAnalytic['entity']);

        $this->assertEquals('shield_v2', $paymentAnalytic['risk_engine']);
    }

    public function testInternationalVisa()
    {
        $this->fixtures->iin->create([
            'iin'     => '426451',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::INTERNATIONAL_VISA,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);

    }

    public function testInternationalMaster()
    {
        $this->fixtures->iin->create([
            'iin'     => '510128',
            'country' => 'US',
            'network' => 'Master',
        ]);
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::INTERNATIONAL_MASTER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);

    }

    public function testInternationalMaestro()
    {
         $this->fixtures->iin->create([
            'iin'     => '589316',
            'country' => 'US',
            'network' => 'Maestro',
        ]);
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::INTERNATIONAL_MAESTRO,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);

    }

    public function testInvalidEci()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card'] = [
                'number'       => CardNumber::INVALID_ECI,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
        ];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $verify = $this->verifyPayment($payment['id']);

        $hitachi = $verify['gateway']['gatewayPayment'];

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertEquals(1, $verify['payment']['verified']);

        $this->assertEquals('status_match', $verify['gateway']['status']);

        //
        // Asserting that the request id from the response is being
        // saved as the  gateway transaction id in the hitachi entity
        //
        $this->assertEquals($verifyResponseContent[Hitachi\ResponseFields::REQUEST_ID],
                            $hitachi[Hitachi\Entity::REQUEST_ID]);

        $this->assertEquals('Success', $hitachi[Hitachi\Entity::STATUS]);
    }

    public function testPaymentVerifyFailed()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockVerifyFailed();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $hitachi = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertEquals('F', $hitachi[Hitachi\Entity::STATUS]);
    }

    public function testCapturePayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $this->assertEquals('captured', $capturedPayment['status']);

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertTestResponse($hitachi, 'testPaymentCaptureEntity');

        $paymentId = explode('_', $capturedPayment['id'])[1];

        $this->assertEquals($paymentId, $hitachi[Hitachi\Entity::PAYMENT_ID]);
        $this->assertNotNull($hitachi[Hitachi\Entity::RRN]);
    }

    public function testCaptureFailure()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $this->assertEquals([], $this->app['gateway_downtime_metric']->getMetrics());

        $this->doAuthPayment($this->payment);

        $this->assertEquals([
            'hitachi' => [
                'SUCCESS'    => [
                    'NO_ERROR'      => 1,
                ],
            ],
        ], $this->app['gateway_downtime_metric']->getMetrics());

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $data = $this->testData['testCaptureFailureException'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->capturePayment($payment['public_id'], $payment['amount']);
            });

        $this->assertEquals([
            'hitachi' => [
                'FAILURE'    => [
                    'GATEWAY_ERROR_UNKNOWN_ERROR'      => 1,
                ],
            ],
        ], $this->app['gateway_downtime_metric']->getMetrics());

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertTestResponse($hitachi, 'testCaptureFailureEntity');
    }

    public function testPaymentRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiRefundEntity'], $gatewayPayment);

    }

    public function testPartialRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(10000, $refund['amount']);

        $this->paymentRefundReverseTestHelper($payment, 10000, 'partial');
    }

    public function testRefundFailure()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->refundReverseFailureTestHelper($payment);
    }

    public function testRefundTimeoutFailure()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT);
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertEquals(explode('_', $refund['id'])[1], $hitachi['refund_id']);

        $this->assertNull($hitachi[Hitachi\Entity::RESPONSE_CODE]);
    }

    public function testPaymentReverse()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundAuthorizedPayment($payment['id']);

        $this->paymentRefundReverseTestHelper($payment);
    }

    public function testReverseFailureDuetoFormatError()
    {
        //All card payments are gateway captured for Razorpay Org ID, so using a different org
        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => Org::HDFC_ORG]);

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFormatErrorOnReversal();

        $this->refundAuthorizedPayment($payment['id']);

        $gatewayEntity = $this->getLastEntity('hitachi', true);

        $this->assertEquals('30', $gatewayEntity['pRespCode']);

        $this->assertNull($gatewayEntity['pRRN']);
    }

    public function testReverseFailure()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $this->refundAuthorizedPayment($payment['id']);

        $this->refundReverseFailureTestHelper($payment);
    }

    public function testInvalidJson()
    {
        $this->mockAuthFormatError();

        $data = $this->testData['testInvalidJson'];

        $this->runRequestResponseFlow(
            $data,
            function ()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::VALID_ENROLL_NUMBER,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }

    public function testPaymentFlowWhenGatewayNullinMpi()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $content = $this->getFormDataFromResponse($response->getContent(), 'http://localhost');

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('created', $payment['status']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->fixtures->edit('mpi', $mpi['id'], ['gateway' => null]);

        $url = 'https://api.razorpay.com/v1/gateway/acs/mpi_blade';

        $this->ba->publicAuth();

        $request = $this->makeFirstGatewayPaymentMockRequest($url, 'POST', $content[2]);

        $this->submitPaymentCallbackRequest($request);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testExpressPayEnrolledForAxisVisa()
    {
         $this->payment['card']['number'] = '4042416376957429';

         $this->expressPayEnrolled('404241','Visa' );
    }

    public function testExpressPayEnrolledForAxisMaster()
    {
        $this->payment['card']['number'] = '5105175541117175';

        $this->expressPayEnrolled('510517', 'Master');
    }

    public function testPinAuthPreferredAuthPin()
    {
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'  => '1',
                'otp'  => '1',
            ]
        ]);

        $this->payment['card']['number'] = '5567630000002004';

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5567630000002004';

        $payment['preferred_auth'] = ['pin'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['auth_type']);

        $gatewayEntity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $gatewayEntity['gateway']);
    }

    public function testExpressPayNotEnrolled()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_generate')
                {
                    $content[Field::RESPONSE_CODE] = '016';

                    $content[Field::RES_DESC] = 'CARD NOT PARTICITIPATING IN 3ds';

                    unset($content[Field::MESSAGE_HASH]);
                }
            }, Gateway::MPI_ENSTAGE

        );

        $this->createIin('402400', 'Visa', 1);

        //Selecting Axis Card
        $this->payment['card']['number'] = '4024001104457538';

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        self::assertFalse($this->otpFlow);
        self::assertTestResponse($payment);
    }

    public function testAuthencationGatewayForAxisMaestro()
    {
        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'Maestro',
            'flows'   => [
                '3ds'    => '1',
                'headless_otp' => '1',
            ],
        ]);

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->doAuthPayment($this->payment);

        $gatewayEnity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $gatewayEnity['gateway']);
    }

    public function testAuthenticationGatewayExpressPayDisabled()
    {
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'MasterCard',
            'flows'   => [
                'otp' => '1',
                '3ds' => '1',
            ],
        ]);

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->doAuthPayment($this->payment);

        $gatewayEnity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $gatewayEnity['gateway']);

        $payment = $this->getLastEntity('payment', true);

        self::assertFalse($this->otpFlow);
        self::assertEquals('authorized', $payment['status']);
        self::assertNull($payment['auth_type']);
        self::assertEquals('hitachi', $payment['gateway']);
    }

    //Enstage only supports AxisExpressPay
    public function testAuthenticationGatewayForHdfc()
    {
        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        //Supports OTP flow for a different gateway
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'MasterCard',
            'flows'   => [
                'otp' => '1',
                '3ds' => '1',
            ],
        ]);

        $this->doAuthPayment($this->payment);

        $gatewayEnity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $gatewayEnity['gateway']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['auth_type']);
    }

    public function testVerifyPaymentwithblankPrn()
    {
        $this->mockBlankPrn();

        $data = $this->testData['testInvalidJson'];

        $this->runRequestResponseFlow(
            $data,
            function ()
            {
                $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::VALID_ENROLL_NUMBER,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    /**
     * Test for payment verification failure when
     * response from gateway is format error.
     */
    public function testVerifyPaymentWithFormatError()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockVerifyFormatError();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $hitachiPayment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $hitachiPayment['verified']);
    }

    public function testMotoTransactionForMaster()
    {
        $motoTerminal = $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->payment['auth_type'] = 'skip';

        unset($this->payment['card']['cvv']);

        $testData = $this->testData['motoTransactionRequest'];

        $this->mockServerRequestFunction(
            function(& $request) use ($testData)
            {
                $this->assertArraySelectiveEquals($testData, $request);
                $this->assertFalse(isset($request['pCVV2']));
            });

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotEmpty($payment['reference2']);

        $this->assertEquals($motoTerminal['id'], $payment['terminal_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('skip', $payment['auth_type']);
    }

    public function testMotoTransactionForVisa()
    {
        $motoTerminal = $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->payment['auth_type'] = 'skip';

        $this->payment['card']['number'] = CardNumber::INTERNATIONAL_VISA;

        unset($this->payment['card']['cvv']);

        $testData = $this->testData['motoTransactionRequest'];

        $this->mockServerRequestFunction(
            function(& $request) use ($testData)
            {
                $this->assertEquals('02', $request['pECI']);
                $this->assertFalse(isset($request['pCVV2']));
            });

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($motoTerminal['id'], $payment['terminal_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('skip', $payment['auth_type']);
    }

    public function testMotoTransactionNotSelectedWhenAuthTypeNotSet()
    {
        $motoTerminal = $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['auth_type']);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function expressPayEnrolled($iin, $network)
    {
        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $this->payment['auth_type'] = 'otp';

        $this->createIin($iin, $network);

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('otp', $payment['auth_type']);

        $gatewayEnity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_enstage', $gatewayEnity['gateway']);

        $this->assertTrue($this->otpFlow);
    }

    public function createIin($iin, $network, $recurring = 0)
    {
        $this->fixtures->iin->create([
            'iin'       => $iin,
            'country'   => 'IN',
            'issuer'    => 'UTIB',
            'network'   => $network,
            'recurring' => $recurring,
            'flows'   => [
                'otp' => '1',
                '3ds' => '1',
            ],
        ]);
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::STATUS] = 'Error';
            }

            if ($action === 'refund')
            {
                $content['pRespCode'] = 'F';
            }
        });

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $response = $this->retryFailedRefund($refund['id'], $payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['refund_id']);

        $this->assertEquals('processed', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::STATUS] = 'Error';
            }

            if ($action === 'refund')
            {
                $content['pRespCode'] = 'F';
            }
        });

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['pRespCode'] = '01';
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['refund_id']);

        $this->assertEquals('processed', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);
    }

    public function testNullResponseInVerifyRefund()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::STATUS] = 'Error';
            }

            if ($action === 'refund')
            {
                $content[ResponseFields::RESPONSE_CODE]      = null;
                $content[ResponseFields::TRANSACTION_TYPE]   = null;
                $content[ResponseFields::REQUEST_ID]         = null;
                $content[ResponseFields::TRANSACTION_AMOUNT] = null;
                $content[ResponseFields::MERCHANT_ID]        = null;
                $content[ResponseFields::MERCHANT_REFERENCE] = null;
                $content[ResponseFields::RETRIEVAL_REF_NUM]  = null;
                $content[ResponseFields::STATUS]             = null;
                $content[ResponseFields::CURRENCY]           = null;
            }
        });

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['pRespCode'] = '01';
            }
        });

        $response = $this->retryFailedRefund($refund['id'], $payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['refund_id']);

        $this->assertEquals('processed', $refund['status']);

        $this->assertEquals(1, $refund['attempts']);
    }

    public function testUnknownEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::UNKNOWN_ENROLLED,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card',
                'international' => true
            ]
        ]);
        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $gatewayPayment = $this->getDbEntity('hitachi', ['action' => 'authorize'])->toArrayAdmin();

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiAuthEntity'], $gatewayPayment);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);
        $this->assertArraySelectiveEquals(
            $this->testData['testTransactionAfterCapture'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testHitachiCaptureEntity'], $gatewayPayment);
    }

    public function testDefinitePaymentVerifyFailed()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData['testVerifyMismatch'];

        $this->mockDefiniteVerifyFailed();

        $e = null;

        try
        {
            $this->verifyPayment($payment['id']);
        }
        catch (PaymentVerificationException $e)
        {
            $this->assertEquals($e->getAction(), 'finish');
        }
        finally
        {
            self::assertNotNull($e);
        }
    }

    public function testBqrPaymentAndRefund()
    {
        $this->markTestSkipped();
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::SC_STOP_QR_AS_RECEIVER_FOR_VIRTUAL_ACCOUNT))
                {
                    return 'control';
                }
                return 'control';
            });

        $request = $this->testData['testBqrPayment'];

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->t1 = $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->t2 = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $this->t3 = $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->qrCode = $this->createVirtualAccount();

        $this->ba->directAuth();

        $qrCodeId = substr($this->qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer()->getBharatQrCallback($qrCodeId);

        // This method tests if the request that contains plain text as input is getting handled properly
        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $bharatQr = $this->getLastEntity('bharat_qr', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        // Notes from the VA are copied over to the payment
        $this->assertArrayHasKey('notes', $payment);
        $this->assertArrayHasKey('key', $payment['notes']);
        $this->assertEquals('value', $payment['notes']['key']);

        $this->assertEquals($bharatQr['payment_id'], $payment['id']);
        $this->assertEquals($bharatQr['expected'], true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['name']);

        $payment = $this->getLastEntity('payment', true);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('rfnd_' . $gatewayPayment['refund_id'], $refund['id']);
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    protected function createRiskConfig()
    {
        $this->ba->adminAuth();

        $request = [
            'content' => [
                'type'       => 'risk',
                'config'     => [
                    'secure_3d_international' => 'v2',
                ],
                'is_default' => true,
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config/bulk',
        ];

        $this->makeRequestAndGetContent($request);
    }
}
