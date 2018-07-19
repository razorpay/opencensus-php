<?php

namespace RZP\Tests\Functional\Gateway\Hitachi;

use RZP\Models\Card;
use RZP\Gateway\Hitachi;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HitachiGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
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
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'hitachi';

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->mockTokenex();
    }

    public function testSuccessful13DigitPanForEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

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
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);
        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);
        $this->assertEquals('100HitachiTmnl', $payment['terminal_id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

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

    public function testInternationalVisa()
    {
        $this->fixtures->iin->create([
            'iin'     => '426451',
            'country' => 'US',
            'network' => 'Visa',
        ]);
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::INTERNATIONAL_VISA,
                'expiry_month' => '02',
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

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
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

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
                'expiry_year'  => '21',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(0, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNull($payment['transaction_id']);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

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
                'expiry_year'  => '21',
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

        $hitachi = $this->getLastEntity('hitachi', true);

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
        $this->assertNull($hitachi[Hitachi\Entity::REQUEST_ID]);
        $this->assertNotNull($hitachi[Hitachi\Entity::RRN]);
    }

    public function testCaptureFailure()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->mockFailureResponseCode();

        $data = $this->testData['testCaptureFailureException'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->capturePayment($payment['public_id'], $payment['amount']);
            });

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

    public function testPaymentReverse()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->refundAuthorizedPayment($payment['id']);

        $this->paymentRefundReverseTestHelper($payment);
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
                        'expiry_year'  => '21',
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

        $this->createIin('402400', 'Visa');

        //Selecting Axis Card
        $this->payment['card']['number'] = '4024001104457538';

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        self::assertFalse($this->otpFlow);
        self::assertTestResponse($payment);
    }

    public function testAuthencationGatewayForAxisMaestro()
    {
        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);

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
        $this->fixtures->merchant->addFeatures('otpelf');

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
        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);

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
                        'expiry_year'  => '21',
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

    public function expressPayEnrolled($iin, $network)
    {
        $this->fixtures->merchant->addFeatures(['otpelf', 'axis_express_pay']);

        $this->payment['auth_type'] = 'otp';

        $this->createIin($iin, $network);

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('otp', $payment['auth_type']);

        $gatewayEnity = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_enstage', $gatewayEnity['gateway']);

        $this->assertTrue($this->otpFlow);
    }

    public function createIin($iin, $network)
    {
        $this->fixtures->iin->create([
            'iin'     => $iin,
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => $network,
            'flows'   => [
                'otp' => '1',
                '3ds' => '1',
            ],
        ]);
    }
}
