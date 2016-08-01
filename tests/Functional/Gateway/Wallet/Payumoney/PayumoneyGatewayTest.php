<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Payumoney;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Base\Otp;
use Carbon\Carbon;
use RZP\Http\Route;

class PayumoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PayumoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'wallet_payumoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertNotEmpty($payment['global_token']);
        $this->assertNotEmpty($payment['global_customer_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testPaymentWithRedirection()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $response = $this->redirectPayment($authPayment['razorpay_payment_id']);

        $this->assertArraySelectiveEquals($authPayment, $response);
    }

    public function testFailedPaymentWithRedirection()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $this->setOtp(Otp::EXPIRED);

        $data = $this->testData[__FUNCTION__];

        $authResponse = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPaymentViaAjaxRoute($payment);
        });

        $content = $this->getJsonContentFromResponse($this->response);

        $data = $this->testData['testExpiredOtpPaymentRedirection'];

        $redirectResponse = $this->runRequestResponseFlow($data, function() use ($content)
        {
            return $this->redirectPayment($content['payment_id']);
        });

        $this->assertArraySelectiveEquals($authResponse, $redirectResponse);
    }

    public function testOtpRetryPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

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

    public function testOtpRetrySuccessPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

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
                            'wallet'        => 'payumoney',
                            'gateway'       => 'wallet_payumoney',
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
                            'wallet'        => 'payumoney',
                            'gateway'       => 'wallet_payumoney',
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
        $payment = $this->getDefaultWalletPaymentArray('payumoney');
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
        // Get Innsufficient balance response
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        // Send topup request
        $response = $this->topupPayment($originalData['payment_id']);

        // Make topup redirection request
        $redirect = $this->sendRequest($response['request']);

        $ret = (($this->isResponseInstanceType($redirect, 'redirect')) and
                ($redirect->getStatusCode() === 302));

        if ($ret === true)
        {
            $callback = array(
                'url' => $redirect->getTargetUrl(),
                'method' => 'get',
                'content' => []
            );

            $callbackResponse = $this->sendRequest($callback);
        }
        else
        {
            assert(false);
        }

        $this->assertArrayHasKey('razorpay_payment_id', $callbackResponse->getOriginalContent()->data);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, __FUNCTION__);

        return $callbackResponse->getOriginalContent()->data;
    }

    public function testTopupAlreadyProcessedPayment()
    {
        $response = $this->testTopupPayment();

        $this->ba->publicAuth();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($response)
        {
            $this->topupPayment($response['razorpay_payment_id']);
        });
    }

    public function testTopupCapturePayment()
    {
        $response = $this->testTopupPayment();

        $capturePayment = $this->capturePayment($response['razorpay_payment_id'], 100000);

        $this->ba->publicAuth();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($response)
        {
            $this->topupPayment($response['razorpay_payment_id']);
        });
    }

    public function testTopupFailedPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment:failed', [
                            'email'         => 'a@b.com',
                            'amount'        => 50000,
                            'contact'       => '9918899029',
                            'method'        => 'wallet',
                            'wallet'        => 'payumoney',
                            'gateway'       => 'wallet_payumoney',
                            'card_id'       => null,
                            'terminal_id'   => $this->sharedTerminal->getId()
                        ]);

        $paymentId = $payment->getPublicId();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($paymentId) {
            $this->topupPayment($paymentId);
        });
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
                            'email'         => 'a@b.com',
                            'amount'        => 50000,
                            'contact'       => '9918899029',
                            'method'        => 'wallet',
                            'wallet'        => 'payumoney',
                            'gateway'       => 'wallet_payumoney',
                            'card_id'       => null,
                            'terminal_id'   => $this->sharedTerminal->id
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
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundPayment2()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);
    }

    public function testRefundExcelFile()
    {
        $defaultPayment = $this->getDefaultWalletPaymentArray('payumoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForPayumoneyWallet();

        $this->assertEquals(4, $data['wallet_payumoney']['count']);
        $this->assertTrue(file_exists($data['wallet_payumoney']['file']));
    }

    public function testRefundExcelFileForAParticularMonth()
    {
        $knownDate = Carbon::create(2016, 5, 21);
        Carbon::setTestNow($knownDate);

        $defaultPayment = $this->getDefaultWalletPaymentArray('payumoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], [
                'created_at' => $createdAt,
                'updated_at' => $createdAt
            ]);
        }

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForPayumoneyWallet(true);

        $this->assertEquals(3, $data['wallet_payumoney']['count']);
        $this->assertTrue(file_exists($data['wallet_payumoney']['file']));

        Carbon::setTestNow();
    }

    protected function generateRefundsExcelForPayumoneyWallet($date = false)
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => 'wallet',
                'wallet'    => 'payumoney',
                'frequency' => 'monthly'
            ],
        );

        if ($date)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function runPaymentCallbackFlowWalletPayumoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;
        $this->callbackUrl  = $url;

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }
        }

        return null;
    }
}
