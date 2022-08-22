<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Olamoney;

use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use Razorpay\Edge\Passport\Passport;
use Razorpay\Edge\Passport\OAuthClaims;
use Razorpay\Edge\Passport\ConsumerClaims;
use Razorpay\Edge\Passport\CredentialClaims;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OlamoneyGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const WALLET = 'olamoney';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OlamoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_olamoney_terminal', ['type' => ['non_recurring' => '1', 'ivr' => '1']]);

        $this->sharedTerminalV2 = $this->fixtures->create('terminal:shared_olamoney_terminal', ['gateway_merchant_id2' => 'v2', 'id' => '1001OlamoneyTl']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'wallet_olamoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'olamoney');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('1000OlamoneyTl', $payment['terminal_id']);

        $this->assertNotEmpty($payment['global_token_id']);

        $this->assertNotEmpty($payment['global_customer_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testPaymentV2()
    {
        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('1001OlamoneyTl', $payment['terminal_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testAmountTampering()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (& $content)
        {
            $content['amount'] = '100.00';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testAmountTamperingV2()
    {
        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (& $content)
        {
            $content['amount'] = '100.00';
        });

        $data = $this->testData['testAmountTampering'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testErrorPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['contact'] = '9008119029';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testErrorPaymentV2()
    {
        $this->fixtures->terminal->disableTerminal($this->sharedTerminal->getId());

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (& $content)
        {
            $content['status'] = 'failed';
        });

        $data = $this->testData['testErrorPayment'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testEmailCellMismatchOnOtpGenerate()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['contact'] = '9022219027';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testThrottlingOnOtpGenerate()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['contact'] = '9022219029';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testOtpRetryPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::INCORRECT);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['two_factor_auth']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);

        $this->step = null;
    }

    public function testCallbackEmptyResponseBody()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content = '';

            return $content;
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }

    public function testOtpRetrySuccessPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::INCORRECT);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('BAD_REQUEST_PAYMENT_OTP_INCORRECT', $payment['internal_error_code']);
        $this->assertEquals(null, $payment['error_code']);

        $this->setOtp(null);

        $data = $this->testData['otpRetryRequest'];
        $data['request']['url'] = $this->callbackUrl;

        $authPayment = $this->makeRequestAndGetContent($data['request']);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPaymentWithOtpAttempts');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testOtpRetryExceededPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
            'method'        => 'wallet',
            'wallet'        => self::WALLET,
            'gateway'       => 'wallet_olamoney',
            'otp_attempts'  => 3,
            'terminal_id'   => $this->sharedTerminal->id
        ]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }

    public function testOtpResendPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
            'method'        => 'wallet',
            'wallet'        => self::WALLET,
            'gateway'       => 'wallet_olamoney',
            'contact'       => '9111111111',
            'otp_attempts'  => 2,
            'otp_count'     => 1,
            'terminal_id'   => $this->sharedTerminal->id
        ]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpResendUrl($payment->getPublicId());

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['otp_attempts'], null);
        $this->assertSame($payment['otp_count'], 2);
    }

    public function testInsufficientBalancePayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);
        $payment['amount'] = 100000;

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

        $response = $this->response->getOriginalContent()->data;

        // Send topup request
        $response = $this->doWalletTopupViaAjaxRoute($response['data']['payment_id']);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testTopUpEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('olamoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
            PaymentEntity::EMAIL        => 'a@b.com',
            PaymentEntity::AMOUNT       => 50000,
            PaymentEntity::CONTACT      => '+919918899029',
            PaymentEntity::METHOD       => 'wallet',
            PaymentEntity::WALLET       => 'olamoney',
            PaymentEntity::GATEWAY      => 'wallet_olamoney',
            PaymentEntity::CARD_ID      => null,
            PaymentEntity::TERMINAL_ID  => $this->sharedTerminal->id
        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $input = ['amount' => $payment['amount']];

        $authPayment = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function (& $content)
        {
            $content[ResponseFields::STATUS] = 'error';

            return $content;
        });

        $this->refundAuthorizedPayment($authPayment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testAuthPaymentRefund');
    }

    public function testRefundFailed()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        // amount for error in refund -- mocked the server accordingly
        $payment['amount'] = 13 * 100;

        $input = ['amount' => $payment['amount']];

        $payment = $this->doAuthPayment($payment);

        $data = $this->testData[__FUNCTION__];

        $paymentId = $payment['razorpay_payment_id'];

        $this->mockServerContentFunction(function (& $content)
        {
            $content[ResponseFields::STATUS] = 'error';

            return $content;
        });

        $refund = $this->refundAuthorizedPayment($paymentId, $input);

        $gatewayRefund = $this->getLastEntity('wallet', true);

        $this->assertSame($gatewayRefund['status_code'], 'error');

        $refundEntity = $this->getDbLastRefund();

        $this->assertEquals($refund['id'], 'rfnd_'.$refundEntity['id']);

        $this->assertEquals('created', $refundEntity['status']);

        return $refund;
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $input = ['amount' => $payment['amount']];

        $payment = $this->doAuthAndCapturePayment($payment);

        $amount = (int) ($payment['amount'] / 3);

        $this->mockServerContentFunction(function (& $content)
        {
            $content[ResponseFields::STATUS] = 'error';

            return $content;
        });

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testPaymentPartialRefund');
    }

    public function testFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $response = $this->redirectPayment($authPayment['razorpay_payment_id']);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArraySelectiveEquals($authPayment, $content);
    }

    public function testFailedPaymentWithPassport()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);
        //add passport for testing passport roles
        $passport = new Passport;
        $passport->identified    = true;
        $passport->authenticated = true;
        $passport->mode          = 'test';
        $passport->roles         = ['oauth.public'];
        //set consumer
        $consumer           = new ConsumerClaims;
        $consumer->id       = '10000000000000';
        $consumer->type     = 'merchant';
        $passport->consumer = $consumer;
        //set credential
        $credential            = new CredentialClaims;
        $credential->username  = 'rzp_test_K1000000000000';
        $passport->credential  = $credential;
        app('request.ctx.v2')->passport = $passport;
        app('request.ctx.v2')->hasPassportJwt = true;

        $response = $this->redirectPayment($authPayment['razorpay_payment_id']);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArraySelectiveEquals($authPayment, $content);
    }

    public function testVerifyRefund()
    {
        $refund = $this->testRefundFailed();

        $this->clearMockFunction();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refundEntity = $this->getDbLastRefund();

        $this->assertEquals($refund['id'], 'rfnd_'.$refundEntity['id']);

        $this->assertEquals(RefundStatus::PROCESSED, $refundEntity['status']);
    }

    protected function failOlamoneyAuthorizePayment()
    {
        $this->mockServerContentFunction(function(& $content)
        {
            $content['status'] = 'failed';

            return $content;
        });

        $this->makeRequestAndCatchException(function()
        {
            $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

            $content = $this->doAuthPayment($payment);
        });
    }

    protected function runPaymentCallbackFlowWalletOlamoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response = $response;

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                $this->callbackUrl = $url;

                return $this->makeOtpCallback($url);
            }

            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackRedirect($url);
        }

        return null;
    }

    public function testPaymentVerifyUpdateGatewayPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
            PaymentEntity::EMAIL        => 'a@b.com',
            PaymentEntity::AMOUNT       => 50000,
            PaymentEntity::CONTACT      => '+919918899029',
            PaymentEntity::METHOD       => 'wallet',
            PaymentEntity::WALLET       => 'olamoney',
            PaymentEntity::GATEWAY      => 'wallet_olamoney',
            PaymentEntity::CARD_ID      => null,
            PaymentEntity::TERMINAL_ID  => $this->sharedTerminal->id
        ]);

        $wallet = $this->fixtures->create('wallet',[
            'action'                => 'authorize',
            'amount'                => '50000',
            'wallet'                => 'olamoney',
            'received'              => true,
            'email'                 => 'a@b.com',
            'contact'               => '9918899029',
            'refund_id'             => null,
            'payment_id'            => $payment['id'],
            'gateway_payment_id'    => ''
        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
        $this->assertEquals('ek78-s35w-ffm8', $wallet['gateway_payment_id']);
    }

    public function testEligibilityFailure()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['contact'] = '9008129412';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
