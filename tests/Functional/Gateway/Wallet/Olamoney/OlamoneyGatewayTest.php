<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Olamoney;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class OlamoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'olamoney';

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/OlamoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_olamoney_terminal');

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

        $this->assertNotEmpty($payment['global_token_id']);

        $this->assertNotEmpty($payment['global_customer_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
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

        $url = $this->getOtpResendUrl($payment);

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
        $response = $this->doWalletTopupViaAjaxRoute($response['payment_id']);

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

        $this->runRequestResponseFlow($data, function() use ($id) {
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

        $this->runRequestResponseFlow($data, function() use ($paymentId, $input)
        {
            $this->refundAuthorizedPayment($paymentId, $input);
        });

        $refund = $this->getLastEntity('wallet', true);

        $this->assertSame($refund['status_code'], 'error');
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $input = ['amount' => $payment['amount']];

        $payment = $this->doAuthAndCapturePayment($payment);

        $amount = (int) ($payment['amount'] / 3);

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

    protected function failOlamoneyAuthorizePayment()
    {
        $server = $this->mockServerContentFunction(function (& $content)
                        {
                            $content['status'] = 'failed';

                            return $content;
                        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

                $content = $this->doAuthPayment($payment);
            });
    }

    protected function runPaymentCallbackFlowWalletOlamoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;


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
}
