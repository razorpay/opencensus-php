<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Payumoney;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Base\Otp;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Http\Route;
use Closure;

class PayumoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayumoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->gateway = 'wallet_payumoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');
    }

    public function testPayment()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertNotEmpty($payment['global_token_id']);
        $this->assertNotEmpty($payment['global_customer_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testPaymentWithRedirection()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $response = $this->redirectPayment($authPayment['razorpay_payment_id']);

        $this->assertTrue($response->headers->contains('Content-Type', 'text/html; charset=UTF-8'));

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArraySelectiveEquals($authPayment, $content);
    }

    public function testPaymentWithCheckBalanceFailure()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'getBalance')
            {
                unset($content['status']);
                $content['message'] = 'Error in fetching wallet limit';
            }
        });

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertNotEmpty($payment['global_token_id']);
        $this->assertNotEmpty($payment['global_customer_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testDebitFailed()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'debit')
            {
                $content['status'] = -1;
                $content['message'] = 'Error in use wallet';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['status'], 'failed');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }

    public function testOtpGenerateFailure()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'otpGenerate')
            {
                $content['status'] = -1;
                $content['message'] = 'OTP couldn\'t be generated';
                $content['errorCode'] = 'unknown';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['status'], 'failed');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }

    public function testFailedPaymentWithRedirection()
    {
        $this->markTestSkipped();

        $this->config['app.throw_exception_in_testing'] = false;
        $this->config['app.debug'] = false;

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $this->setOtp(Otp::EXPIRED);

        $data = $this->testData[__FUNCTION__];

        $authResponse = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertArraySelectiveEquals($data, $authResponse);

        $payment = $this->getLastEntity('payment');

        $response = $this->redirectPayment($payment['id']);
        $headers = $response->headers;

        $this->assertTrue($headers->contains('Content-Type', 'text/html; charset=UTF-8'), 'Content-Type should be text/html');

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArraySelectiveEquals($authResponse, $content);
    }

    public function testOtpRetryPayment()
    {
        $this->markTestSkipped();

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
        $this->markTestSkipped();

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
        $this->markTestSkipped();

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
        $this->markTestSkipped();

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

        $url = $this->getOtpResendUrl($payment->getPublicId());

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['otp_attempts'], null);
        $this->assertSame($payment['otp_count'], 2);
    }

    public function testOtpResendOnAuthorizedPayment()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $response = $this->doAuthPayment($payment);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpResendUrl($response['razorpay_payment_id']);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }

    public function testInsufficientBalancePayment()
    {
        $this->markTestSkipped();

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

    public function testWalletLimitExceededPayment()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $data = $this->testData[__FUNCTION__];

        $this->setOtp(Otp::WALLET_LIMIT_EXCEEDED);

        $this->mockServerContentFunction(function(& $content)
        {
            $content['result']['maxLimit'] = 0;
            $content['result']['availableBalance'] = 0;
        });

        $response = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });

        return $response;
    }

    public function testTopupPayment()
    {
        $this->markTestSkipped();

        // Get Innsufficient balance response
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        // Send topup request
        $response = $this->doWalletTopupViaAjaxRoute($originalData['data']['payment_id']);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, __FUNCTION__);

        return $response;
    }

    public function testTopupWithFailedStatus()
    {
        $this->markTestSkipped();

        // Get Innsufficient balance response
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        $this->mockServerContentFunction(function(& $content)
        {
            $content['status'] = 'failure';
        });

        $data = $this->testData['testTopupFailed'];

        // Send topup request
        $response = $this->runRequestResponseFlow($data, function() use ($originalData)
        {
            return $this->doWalletTopupViaAjaxRoute($originalData['data']['payment_id']);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }

    public function testTopupWithoutStatus()
    {
        $this->markTestSkipped();

        // Get Innsufficient balance response
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        $this->mockServerContentFunction(function(& $content)
        {
            unset($content['status']);
        });

        $data = $this->testData['testTopupFailed'];

        // Send topup request
        $response = $this->runRequestResponseFlow($data, function() use ($originalData)
        {
            return $this->doWalletTopupViaAjaxRoute($originalData['data']['payment_id']);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }

    public function testTopupPaymentViaRedirectionFlow()
    {
        $this->markTestSkipped();

        // Get Innsufficient balance response
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        $response = $this->doWalletTopup($originalData['data']['payment_id']);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testTopupPayment');
    }

    public function testTopupAlreadyProcessedPayment()
    {
        $this->markTestSkipped();

        $response = $this->testTopupPayment();

        $this->ba->publicAuth();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($response)
        {
            $this->doWalletTopupViaAjaxRoute($response['razorpay_payment_id']);
        });
    }

    public function testTopupCapturePayment()
    {
        $this->markTestSkipped();

        $response = $this->testTopupPayment();

        $capturePayment = $this->capturePayment($response['razorpay_payment_id'], 100000);

        $this->ba->publicAuth();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($response)
        {
            $this->doWalletTopupViaAjaxRoute($response['razorpay_payment_id']);
        });
    }

    public function testTopupFailedPayment()
    {
        $this->markTestSkipped();

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

        $data = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($data, function() use ($paymentId) {
            $this->doWalletTopupViaAjaxRoute($paymentId);
        });
    }

    public function testVerifyPayment()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyPaymentMismatch()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $response = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function(& $content)
        {
            $content['result'][0]['status'] = 'failure';
        });

        $data = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($data, function() use ($response)
        {
            return $this->verifyPayment($response['razorpay_payment_id']);
        });
    }

    public function testVerifyFailedPayment()
    {
        $this->markTestSkipped();

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
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPartialRefundPayment()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $refundAmount = $payment['amount'] / 5;

        $this->mockServerContentFunction(function(& $content, $action) use ($refundAmount)
        {
            if ($action === 'validateRefund')
            {
                $actualRefundAmount = (int) ($content['refundAmount'] * 100);

                $assertion = ($actualRefundAmount === $refundAmount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }

            if ($action === 'refund')
            {
                $content['result'] = '123456';
            }
        });

        $this->refundPayment($capturePayment['id'], $refundAmount);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundPayment2()
    {
        $this->markTestSkipped();

        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);
    }

    public function testRefundExcelFile()
    {
        $this->markTestSkipped();

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
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForPayumoneyWallet();

        $this->assertEquals(4, $data['wallet_payumoney']['count']);
        $this->assertTrue(file_exists($data['wallet_payumoney']['file']));

        unlink($data['wallet_payumoney']['file']);
    }

    public function testRefundExcelFileForAParticularMonth()
    {
        $this->markTestSkipped();

        $knownDate = Carbon::create(2016, 5, 21, null, null, null);
        Carbon::setTestNow($knownDate);

        $defaultPayment = $this->getDefaultWalletPaymentArray('payumoney');

        $payment = $this->doAuthAndCapturePayment($defaultPayment);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $payment = $this->doAuthAndCapturePayment($defaultPayment);
        $refund = $this->refundPayment($payment['id']);

        $createdAt = Carbon::today(Timezone::IST)->addMonth(1)->timestamp + 5;

        $this->fixtures->edit('refund', $refund['id'], [
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);

        $data = $this->generateRefundsExcelForPayumoneyWallet(true);

        $this->assertEquals(3, $data['wallet_payumoney']['count']);
        $this->assertTrue(file_exists($data['wallet_payumoney']['file']));

        unlink($data['wallet_payumoney']['file']);

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
