<?php

namespace RZP\Tests\Functional\Gateway\Mobikwik;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Base\Otp;

class MobikwikGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/MobikwikGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mobikwik';

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['wallet'] = 'mobikwik';
        $this->payment['method'] = 'wallet';
    }

    public function testPayment()
    {

        $this->payment = $this->doAuthAndCapturePayment($this->payment);
        $this->payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($this->payment);

        $this->payment = $this->getLastEntity('mobikwik', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testMobikwikWalletEntity'], $this->payment);
    }

//    public function testFailedPayment()
//    {
//        $this->markTestIncomplete();
//    }

    public function testPowerWalletPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $mobikwik = $this->getLastEntity('mobikwik', true);

        $this->assertTestResponse($mobikwik, 'testMobikwikWalletEntity');
    }

    public function testPowerWalletOtpRetryPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::INCORRECT);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_OTP_INCORRECT');
        $this->assertEquals($payment['error_code'], null);

        $this->setOtp(null);

        $data = $this->testData['otpRetryRequest'];
        $data['request']['url'] = $this->callbackUrl;

        $authPayment = $this->makeRequestAndGetContent($data['request']);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentWithOtpAttempts');

        $mobikwik = $this->getLastEntity('mobikwik', true);

        $this->assertTestResponse($mobikwik, 'testMobikwikWalletEntity');
    }

    public function testOtpRetryExceededPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
                            'method'        => 'wallet',
                            'wallet'        => 'mobikwik',
                            'gateway'       => 'mobikwik',
                            'otp_attempts'  => 3,
                            'terminal_id'   => $this->sharedTerminal->id
                        ]);

        $data = $this->testData[__FUNCTION__];

        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $payment->getPublicId(), $secret);

        $params = ['id' => $payment->getPublicId(), 'hash' => $hash, 'key_id' => $this->ba->getKey()];

        $url = \URL::route('payment_otp_submit', $params, false);
        $url = 'http://localhost' . $url;

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }

    public function testOtpResendPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
                            'method'        => 'wallet',
                            'wallet'        => 'mobikwik',
                            'gateway'       => 'mobikwik',
                            'contact'       => '9111111111',
                            'otp_attempts'  => 2,
                            'otp_count'     => 1,
                            'terminal_id'   => $this->sharedTerminal->id
                        ]);

        $data = $this->testData[__FUNCTION__];

        $params = ['id' => $payment->getPublicId(), 'key_id' => $this->ba->getKey()];
        $wallet = $this->getLastEntity('terminal', true);

        $url = \URL::route('payment_otp_resend', $params, false);
        $url = 'http://localhost' . $url;

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['otp_attempts'], null);
        $this->assertSame($payment['otp_count'], 2);
    }

    public function testInsufficientBalancePayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::INSUFFICIENT_BALANCE);

        $response = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });

        return $response;
    }

    public function testTopupPayment()
    {
        // Get Insufficient balance response
        $response = $this->testInsufficientBalancePayment();

        $responseData = $this->response->original->data;

        $topupRequest = $this->testData['topupData'];

        // Send topup request
        $topupResponse = $this->topupPayment($responseData['payment_id']);

        // Make topup redirection request
        $topupRedirect = $this->sendRequest($topupResponse['request']);

        $ret = (($this->isResponseInstanceType($topupRedirect, 'redirect')) and
            ($topupRedirect->getStatusCode() === 302));

        if ($ret === true)
        {
            $callback = array(
                'url' => $topupRedirect->getTargetUrl(),
                'method' => 'get',
                'content' => []
            );

            $callbackResponse = $this->sendRequest($callback);
        }
        else
        {
            assert(false);
        }

        $this->assertArrayHasKey('razorpay_payment_id', $callbackResponse->original->data);

        $mobikwik = $this->getLastEntity('mobikwik', true);

        $this->assertTestResponse($mobikwik, 'testPaymentMobikwikEntity');
    }

    public function testVerifyPayment()
    {
        $this->payment = $this->doAuthAndCapturePayment($this->payment);
        $id = $this->payment['id'];
        $this->payment = $this->verifyPayment($id);
        $this->assertEquals($this->payment['payment']['verified'], 1);
    }

    public function testPowerWalletVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $id = $capturePayment['id'];

        $response = $this->verifyPayment($id);

        $this->assertEquals($response['payment']['verified'], 1);
    }

    public function testPowerWalletVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
                            'email'         => 'a@b.com',
                            'amount'        => 50000,
                            'contact'       => '9918899029',
                            'method'        => 'wallet',
                            'wallet'        => 'mobikwik',
                            'gateway'       => 'mobikwik',
                            'card_id'       => null,
                            'terminal_id'   => $this->sharedTerminal->id
                        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id) {
            $this->verifyPayment($id);
        });

        $mobikwik = $this->getLastEntity('mobikwik', true);

        $this->assertTestResponse($mobikwik, 'testMobikwikWalletEntity');

    }

    public function testRefundPayment()
    {
        $this->payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($this->payment['id']);
        $refund = $this->getLastEntity('mobikwik', true);
        $this->assertTestResponse($refund);
    }

    public function testNonExistingWalletUserPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('mobikwik');
        $payment['_']['source'] = 'checkoutjs';

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::USER_DOES_NOT_EXIST);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
