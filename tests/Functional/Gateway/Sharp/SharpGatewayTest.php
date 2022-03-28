<?php

namespace RZP\Tests\Functional\Gateway\Sharp;

use Cache;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SharpGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/SharpGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';

        $this->fixtures->create('iin',
            [
                'iin'           => '400666',
                'category'      => 'STANDARD',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'STATE BANK OF INDI',
                'issuer'        => 'SBIN',
                'recurring'     => '1'
            ]);

        $this->mockCardVault();
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }

    public function testCardlessEmiPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'cardless_emi');

        $payment = $this->getDefaultCardlessEmiPaymentArray('earlysalary');

        $payment['contact'] = '+91' . $payment['contact'];

        $this->setOtp('123456');

        $response = $this->doAuthPayment($payment);

        $this->assertNotNull($response['razorpay_payment_id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testPaylaterPayment()
    {
        $this->fixtures->merchant->enablePayLater('10000000000000');

        $payment = $this->getDefaultPayLaterPaymentArray('icic');

        $payment['contact'] = '7602579721';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($response['razorpay_payment_id']);

        $this->assertTestResponse($payment);
    }

    public function testCardlessEmiPaymentSubProvider()
    {
        $this->fixtures->merchant->enableCardlessEmi('10000000000000');

        $this->provider = 'kkbk';

        $payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        unset($payment['emi_duration']);

        $payment['contact'] = '+91' . $payment['contact'];

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testPaylaterPaymentSubProvider()
    {
        $this->fixtures->merchant->enablePayLater('10000000000000');

        $this->provider = 'hdfc';

        $payment = $this->getDefaultPayLaterPaymentArray($this->provider);

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testUpiPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $this->assertEquals('async', $response['type']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentId, $payment['id']);

        $this->assertEquals($payment['status'], 'authorized');

        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->assertNotNull($payment['reference16']);

        $this->assertNotNull($payment['reference1']);

        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        $this->assertNotEmpty($payment['vpa']);
    }

    public function testGooglePayCardPayment()
    {
        $order = $this->fixtures->create('order');

        $googlePayPaymentCreateRequestData = $this->testData['googlePayPaymentCreateRequestData'];

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $googlePayPaymentCreateRequestData['order_id'] = $order->getPublicId();

        $googlePayPaymentCreateRequestData['amount']   = $order['amount'];

        $googlePayPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        $this->fixtures->merchant->addFeatures([Feature\Constants::GOOGLE_PAY_CARDS]);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertEquals($response['type'], 'application');

        $this->assertEquals($response['application_name'], 'google_pay');

        $this->assertEquals($response['request']['method'], 'sdk');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['authentication_gateway'], 'google_pay');
    }

    public function testIntentPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['description']);
        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentId, $payment['id']);

        $this->assertEquals($payment['status'], 'authorized');

        $this->assertNotEmpty($payment['vpa']);
    }

    public function testFailedIntentPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['description']);
        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $payment['amount'] = 5555;

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentId, $payment['id']);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testEmandatePaymentWithoutOrderId()
    {
        $this->fixtures->merchant->enableEmandate('10000000000000');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar');
        $payment['amount'] = 0;
        $payment['aadhaar']['number'] = '123456789012';
        $payment['bank_account'] = [
            'account_number'   => '914010009305862',
            'ifsc'             => 'HDFC0002766',
            'name'             => 'Test account',
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testEmandatePaymentWithDifferentOrderAmount()
    {
        $this->fixtures->merchant->enableEmandate('10000000000000');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar');
        $payment['amount'] = 0;
        $payment['aadhaar']['number'] = '123456789012';
        $payment['bank_account'] = [
            'account_number'   => '914010009305862',
            'ifsc'             => 'HDFC0002766',
            'name'             => 'Test account',
            'account_type'     => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 100]);
        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testAadhaarEmandatePayment()
    {
        $this->markTestSkipped('aadhar auth type not supported');

        $this->fixtures->merchant->enableEmandate('10000000000000');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getEmandatePaymentArray('HDFC', 'aadhaar');
        $payment['aadhaar']['number'] = '123456789012';
        $payment['bank_account'] = [
            'account_number'   => '914010009305862',
            'ifsc'             => 'HDFC0002766',
            'name'             => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $paymentEntity = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $this->assertEquals('aadhaar', $paymentEntity['auth_type']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000SharpTrmnl', $paymentEntity['terminal_id']);
    }

    public function testAadhaarEmandatePaymentInvalidBank()
    {
        $this->fixtures->merchant->enableEmandate('10000000000000');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getEmandatePaymentArray('VIJB', 'aadhaar', 0);

        $payment['aadhaar']['number'] = '123456789012';
        $payment['bank_account'] = [
            'account_number'   => '914010009305862',
            'ifsc'             => 'HDFC0002766',
            'name'             => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentAuthenticateCard()
    {
        $this->fixtures->merchant->addFeatures('charge_at_will');
        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('1000SharpTrmnl', $paymentEntity['terminal_id']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $paymentEntity['recurring']);

        $response = $this->doAuthPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('1000SharpTrmnl', $paymentEntity['terminal_id']);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $paymentEntity['recurring']);

        $token = $paymentEntity['token_id'];

        unset($payment['card']);

        // Set payment for subsequent recurring payment
        $payment['token'] = $token;

        // Switch to private auth for subsequent recurring payment
        $this->ba->privateAuth();

        $response = $this->doS2sRecurringPayment($payment);
        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals('1000SharpTrmnl', $paymentEntity['terminal_id']);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $paymentEntity['recurring']);
    }

    public function testRecurringHardDeclinePaymentAuthenticateCard()
    {
        $this->fixtures->merchant->addFeatures('charge_at_will');
        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['amount'] = '5555';
        $payment['card']['number'] = '4006660000000007';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringSoftDeclinePaymentAuthenticateCard()
    {
        $this->fixtures->merchant->addFeatures('charge_at_will');
        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['amount'] = '4444';
        $payment['card']['number'] = '4006660000000007';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithGet()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4111111111111111';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
    }


    public function testPaymentFailed()
    {
        $this->failPaymentOnBankPage = true;

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();
        $testData['request']['content'] = $payment;
        $this->startTest($testData);
    }

    public function testOtpFlowInsufficientBalancePayment()
    {
        $this->otpCommonFlow('100000');
    }

    public function testOtpFlowIncorrectOtpPayment()
    {
        $this->otpCommonFlow('200000');
    }

    public function testOtpFlowOtpExpiredPayment()
    {
        $this->otpCommonFlow('300000');
    }

    public function testOtpFlowAttemptsExceededPayment()
    {
        $this->otpCommonFlow('400000');
    }

    public function testValidateVpaSuccess()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->startTest();
    }

    public function testValidateVpaFailure()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->startTest();
    }



    public function validateVpaCardNumberLikeVpa()
    {
        return $cases = [
            'invalid_card_number_without_special_character'  => ['4012001038443325@razorpay'],
            'invalid_card_number_with_hyphen'                => ['4012-0010-3844-3325@razorpay'],
            'invalid_card_number_with_prefix'                => ['ccpay.4012001038443325@razorpay'],
            'invalid_card_number_with_suffix'                => ['4012-0010-3844-3325.ccpay@razorpay'],
        ];
    }

    /**
     * @dataProvider validateVpaCardNumberLikeVpa
     */
    public function testValidateVpaCardNumberLikeVpa($vpa)
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->startTest([
            'request' => [
                'content' => [
                    'vpa'   => $vpa,
                ],
            ],
            'response' => [
                'content' => [
                    'success'   => true,
                ],
            ],
        ]);
    }

    public function testValidateVpaInvalid()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->startTest();
    }

    public function testValidateVpaStrUpper()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->startTest();
    }

    public function testValidateVpaForForbiddenMerchant()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    /*
     * Creating mindgate terminal as sharp will do direct s2spayment for upi.
     * we need to avoid that and check the response flow from cache
    */
    public function testUpiPaymentCacheFlow()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->gateway = 'upi_mindgate';

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $this->setupCacheMock(str_after($paymentId, 'pay_'));

        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');

        //to make sure the data is from cache
        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(['success' => true], $response);

        $response = $this->checkPaymentStatus($paymentId, null);

        $this->assertNotNull($response['razorpay_payment_id']);

        $this->assertEquals($response['razorpay_payment_id'], $payment['id']);
    }

    public function testUpiPaymentCacheMissFlow()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->gateway = 'upi_mindgate';

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $this->setupCacheMissMock(str_after($paymentId, 'pay_'));

        $this->assertEquals('async', $response['type']);

        // catches the exception
        $this->checkPaymentStatus($paymentId, 'created');
    }

    public function testOtmSharpPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->editAutoRefundDelay('2 days');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $upiMetadata = $this->getLastEntity('upi_metadata', true);

        $this->assertArraySubset([
            'type' => 'otm',
            'flow' => 'collect',
        ], $upiMetadata, true);

        $payment = $this->getLastEntity('payment', true);

        // Assert that payment refund_at is set to 2 days (which is default) after upi_metadata end time,
        // as merchant can capture it between start_time and end_time.
        $refundDelay = ($payment['refund_at'] - $upiMetadata['end_time']) / (24 * 60 * 60);
        $this->assertSame(2, $refundDelay);

        // Assert it is never gateway_captured while authorizing
        $this->assertSame(null, $payment['gateway_captured']);

        // Execute the mandate by calling capture.
        $this->capturePayment($paymentId, $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        // Assert that it is gateway_captured now
        $this->assertArraySubset([
            'status'           => 'captured',
            'gateway_captured' => true,
        ], $payment);
    }

    public function testOtmInvalidExecute()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        // Setting the start_time for 1 day after now, so now we cannot execute today.
        $payment['upi']['start_time'] = Carbon::now()->addDays(1)->getTimestamp();

        unset($payment['upi']['end_time']);

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function () use ($payment, $response)
        {
           $this->capturePayment($response['payment_id'], $payment['amount']);
        });

        $payment = $this->getLastEntity('payment', true);

        // Assert that payment is still authorized, after a failed capture.
        $this->assertSame('authorized', $payment['status']);
    }

    public function testOtmRefundAuthorized()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Refund a authorized payment, as it is otm, it will mandate revoke.
        $this->refundPayment($paymentId, $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        // Assert it went to refunded.
        $this->assertSame('refunded', $payment['status']);
    }

    public function testOtmRefundAuthorizedFailWithPartialAmount()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Refund a authorized payment, as it is otm, it will mandate revoke.
        $this->makeRequestAndCatchException(function () use ($paymentId, $payment)
        {
            $this->refundPayment($paymentId, $payment['amount'] - 100);
        },
        Exception\BadRequestException::class,
        'Void is not supported for partial refunds');
    }

    public function testOtmRefundAuthorizedFailsWithVoidEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::VOID_REFUNDS]);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Refund a authorized payment, as it is otm, it will mandate revoke.
        $this->makeRequestAndCatchException(function () use ($paymentId, $payment)
        {
            $this->refundPayment($paymentId, $payment['amount'] - 100);
        },Exception\BadRequestException::class,
        'Void is not supported for partial refunds');
    }

    public function testOtmRefundAuthorizedPassWithVoidEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::VOID_REFUNDS]);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $this->refundPayment($paymentId, $payment['amount']);

        $payment = $this->getLastPayment();

        $this->assertSame('refunded', $payment['status']);
    }

    public function testUpiOtmInvalidExecuteAfterEndTime()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();
        unset($payment['upi']['start_time']);
        $payment['upi']['end_time'] = Carbon::now()->addSecond(45)->getTimestamp();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $actualNow = Carbon::now();

        $after5Minute = Carbon::now()->addMinute(5);

        Carbon::setTestNow($after5Minute);

        $this->makeRequestAndCatchException(function () use ($response, $payment)
        {
            $this->capturePayment($response['payment_id'], $payment['amount']);
        },
        Exception\BadRequestValidationFailureException::class,
        'Execution only allowed between start time and end time');

        Carbon::setTestNow($actualNow);
    }

    public function testOtmRefundCaptured()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        $this->capturePayment($paymentId, $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('captured', $payment['status']);

        // Assert refund processed
        $data = $this->refundPayment($paymentId);
        $this->assertSame('processed', $data['status']);

        // Assert payment refunded
        $payment = $this->getLastEntity('payment', true);
        $this->assertSame('refunded', $payment['status']);
    }

    public function testOtmAutoCaptureBlocked()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $order = $this->createOrder([
           'payment_capture' => true
        ]);

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['order_id'] = $order['id'];

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        },
        Exception\BadRequestException::class,
        'Auto capture is not allowed for upi mandates.');
    }

    public function testOtmInvalidEndTimeOutOfRange()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['upi']['start_time'] = Carbon::now()->getTimestamp();
        $payment['upi']['end_time'] = Carbon::now()->addDays(90)->addMinute(1)->getTimestamp();

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        },
         Exception\BadRequestValidationFailureException::class,
        'End time provided for upi mandate is out of range');
    }

    public function testOtmPaymentWithFailVpa()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['upi']['vpa'] = 'failure@razorpay';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getLastPayment();

        $this->assertSame('failed', $payment['status']);
    }

    public function testOtmPaymentInLiveModeTestVpaFails()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['upi']['vpa'] = 'failure@razorpay';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $this->doAuthPaymentViaAjaxRoute($payment);
        },
        Exception\BadRequestException::class,
        'Your UPI application does not support one time mandate.');
    }

    public function testHeadlessAuthenticationPayment()
    {
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('sharp', $payment['gateway']);
        self::assertEquals('1000SharpTrmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpPaymentFallbackTo3ds()
    {
        $this->fixtures->iin->edit('411111',[
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
                'otp'          => '0',
                'ivr'          => '0',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111111111111160';

        $this->setOtp('213433');

        $response = $this->doAuthPayment($payment);

        self::assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('authorized', $payment['status']);
        self::assertEquals(null, $payment['auth_type']);
        self::assertEquals('sharp', $payment['gateway']);
        self::assertEquals('1000SharpTrmnl', $payment['terminal_id']);
    }

    public function testHeadlessOtpPaymentBlockedCard()
    {
        $this->fixtures->iin->edit('411111',[
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
                'otp'          => '0',
                'ivr'          => '0',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['otp_auth_default']);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111111111111145';

        $this->setOtp('213433');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

    }

    public function testHeadlessAuthenticationPaymentS2S()
    {
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);
        $this->mockCardVault();

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $payment['ip']         = '52.34.123.23';
        $payment['user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);
        $content = $this->getJsonContentFromResponse($response);

        self::assertArrayHasKey('next', $content);
        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals('created', $payment['status']);
        self::assertEquals('headless_otp', $payment['auth_type']);
        self::assertEquals('sharp', $payment['gateway']);
        self::assertEquals('1000SharpTrmnl', $payment['terminal_id']);

        $response = $this->doS2SOtpSubmitCallback($content, '123456');

        self::assertArrayHasKey('razorpay_payment_id', $content);
        self::assertEquals($content['razorpay_payment_id'], $response['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);
        self::assertEquals('authorized', $payment['status']);
    }

    protected function otpCommonFlow($otp)
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'mobikwik');

        $this->ba->publicAuth();

        $this->setOtp($otp);

        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];
        $data = $this->testData[$name];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        if($expectedStatus === null)
        {
            return $response;
        }

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    protected function setupCacheMock($paymentId)
    {
        $key = 'payment:upi.polling.' . $paymentId . '.status';

        $store = Cache::store();

        Cache::shouldReceive('store')->andReturn($store);

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });


         Cache::shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturnUsing(function()
            {
                return null;
            });

        Cache::shouldReceive('put')
            ->once()
            ->with($key, ['status' => 'created'], 45)
            ->andReturn(null);

        Cache::shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturnUsing(function()
            {
                return ['status' => 'created'];
            });

        Cache::shouldReceive('forget')
            ->once()
            ->with($key)
            ->andReturnUsing(function()
            {
                return null;
            });

        Cache::shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturnUsing(function()
            {
                return null;
            });
    }

    protected function setupCacheMissMock($paymentId)
    {
        $key = 'payment:upi.polling.' . $paymentId . '.status';

        $store = Cache::store();

        Cache::shouldReceive('store')->andReturn($store);

        Cache::shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturnUsing(function()
            {
                throw new Exception\RuntimeException(
                    'Test Exception');
            });
    }

    protected function doS2SOtpSubmitCallback(array $content, string $otp)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $content['razorpay_payment_id'] . '/otp/submit',
            'content' => [
                'otp' => $otp
            ],
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function testVisaSafeClickCardPayment()
    {
        $this->fixtures->merchant->addFeatures(['vsc_authorization']);

        $authentication = array(
            'cavv'                  => '3q2+78r+ur7erb7vyv66vv\/\/8=',
            'cavv_algorithm'        => '1',
            'eci'                   => '05',
            'xid'                   => 'ODUzNTYzOTcwODU5NzY3Qw==',
            'enrolled_status'       => 'Y',
            'authentication_status' => 'Y',
            'provider_data'         => [
                'product_transaction_id'        => '1_156049293_714_62_l73q001m_CHECK211_156049293_714_62_l73q00',
                'product_merchant_reference_id' => '4aa1c9ffd4fc7ded80f73f1d98b35e8e24085404b6e01401',
                'product_type'                  => 'VCIND',
                'auth_type'                     => '3ds'
            ]
        );

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['application'] = 'visasafeclick';
        $paymentArray['authentication'] = $authentication;

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['authentication_gateway'], 'visasafeclick');
        $this->assertEquals($payment['status'], 'authorized');
        $this->assertNotNull($payment['acquirer_data']['product_enrollment_id']);
    }
}
