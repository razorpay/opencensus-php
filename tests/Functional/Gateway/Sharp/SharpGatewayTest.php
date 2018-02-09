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

        $this->fixtures->merchant->addFeatures(['upi_intent']);

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

        $this->fixtures->merchant->addFeatures(['upi_intent']);

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

    protected function otpCommonFlow($otp)
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->ba->publicAuth();

        $this->setOtp($otp);

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];
        $data = $this->testData[$name];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
