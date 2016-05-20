<?php

namespace Tests\Functional\Gateway\Wallet\Payumoney;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;
use Carbon\Carbon;
use Http\Route;

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

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testOtpRetryPayment()
    {
        $this->step = 'RETRY';

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);

        $this->step = null;
    }

    public function testOtpRetrySuccessPayment()
    {
        $this->step = 'RETRY';

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['internal_error_code'], 'BAD_REQUEST_PAYMENT_OTP_INCORRECT');
        $this->assertEquals($payment['error_code'], null);

        $this->step = null;

        $data = $this->testData['otpRetryRequest'];
        $data['request']['url'] = $this->otpSubmitUrl;

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
                            'wallet'        => 'payumoney',
                            'gateway'       => 'wallet_payumoney',
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
        $this->step = 'TOPUP';

        $payment = $this->getDefaultWalletPaymentArray('payumoney');
        $payment['amount'] = 100000;

        $data = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });

        $this->step = null;

        return $response;
    }

    public function testTopupPayment()
    {
        // Get Innsufficient balance response
        $response = $this->testInsufficientBalancePayment();

        $this->step = 'TOPUP';

        $responseData = $this->response->original->data;

        $topupRequest = $this->testData['topupData'];

        // Generate relative URL for topup
        $url = \URL::route('payment_topup_ajax', ['id' => $responseData['payment_id']], false);
        $url = 'http://localhost' . $url;

        $topupRequest['request']['url'] = $url;

        // Send topup request
        $topupResponse = $this->runRequestResponseFlow($topupRequest);

        // Make topup redirection request
        $topupRedirect = $this->makeRequest($topupResponse['request']);

        $ret = (($this->isResponseInstanceType('redirect', $topupRedirect)) and
            ($topupRedirect->getStatusCode() === 302));

        if ($ret === true)
        {
            $callback = array(
                'url' => $topupRedirect->getTargetUrl(),
                'method' => 'get',
                'content' => []
            );

            $callbackResponse = $this->makeRequest($callback);
        }
        else
        {
            assert(false);
        }

        $this->assertArrayHasKey('razorpay_payment_id', $callbackResponse->original->data);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, __FUNCTION__);

        $this->step = null;

        return $callbackResponse->original->data;
    }

    public function testTopupAlreadyProcessedPayment()
    {
        $responseData = $this->testTopupPayment();

        $this->ba->publicAuth();
        $this->step = 'TOPUP';

        $topupRequest = $this->testData['topupDataAlreadyProcessed'];

        // Generate relative URL for topup
        $url = \URL::route('payment_topup_ajax', ['id' => $responseData['razorpay_payment_id']], false);
        $url = 'http://localhost' . $url;

        $topupRequest['request']['url'] = $url;

        // Send topup request
        $this->runRequestResponseFlow($topupRequest);
    }

    public function testTopupCapturePayment()
    {
        $responseData = $this->testTopupPayment();

        $capturePayment = $this->capturePayment($responseData['razorpay_payment_id'], 100000);

        $this->ba->publicAuth();
        $this->step = 'TOPUP';

        $topupRequest = $this->testData['topupDataAlreadyProcessed'];

        // Generate relative URL for topup
        $url = \URL::route('payment_topup_ajax', ['id' => $responseData['razorpay_payment_id']], false);
        $url = 'http://localhost' . $url;

        $topupRequest['request']['url'] = $url;

        // Send topup request
        $this->runRequestResponseFlow($topupRequest);
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

        $topupRequest = $this->testData['topupDataAlreadyProcessed'];

        // Generate relative URL for topup
        $url = \URL::route('payment_topup_ajax', ['id' => $paymentId], false);
        $url = 'http://localhost' . $url;

        $topupRequest['request']['url'] = $url;

        // Send topup request
        $this->runRequestResponseFlow($topupRequest);
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

        $this->runRequestResponseFlow($data, function() use ($id) {
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

        $this->assertEquals($data['wallet_payumoney']['count'], 3);
    }

    protected function generateRefundsExcelForPayumoneyWallet()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/wallet/excel/monthly',
            'method' => 'post',
            'content' => [
                'wallet'  => 'payumoney'
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function runPaymentCallbackFlowWalletPayumoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;
        $this->otpSubmitUrl = $url;

        if ($mock)
        {
            $content['otp'] = '111111';

            if (isset($this->step))
            {
                switch ($this->step)
                {
                    case 'RETRY':
                        $content['otp'] = '121212';
                        break;
                }
            }

            $content['type'] = 'otp';

            $request = array(
                'url'       => $url,
                'method'    => $method,
                'content'   => $content
            );

            return $this->makeRequest($request);
        }

        return null;
    }
}
