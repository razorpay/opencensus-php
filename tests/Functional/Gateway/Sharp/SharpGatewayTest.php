<?php

namespace RZP\Tests\Functional\Gateway\Sharp;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class SharpGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/SharpGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';

        $this->mockTokenex();
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'captured');
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

        $this->assertNotEmpty($payment['vpa']);
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

        $payment = $this->getEmandatePaymentArray('SBIN', 'aadhaar', 0);

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

    public function testValidateVpaInvalid()
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

    protected function otpCommonFlow($otp)
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'olamoney');

        $this->ba->publicAuth();

        $this->setOtp($otp);

        $payment = $this->getDefaultWalletPaymentArray('olamoney');

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];
        $data = $this->testData[$name];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
