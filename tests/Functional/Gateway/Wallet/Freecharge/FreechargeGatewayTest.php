<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Freecharge;

use Carbon\Carbon;

use RZP\Http\Route;
use RZP\Gateway\Wallet;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Freecharge\ResponseFields;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class FreechargeGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'freecharge';

    protected $payment;

    protected $merchantId = '10000000000000';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/FreechargeGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_freecharge_terminal');

        $this->gateway = 'wallet_freecharge';

        $this->walletRepo = new Wallet\Base\Repository;

        $this->fixtures->merchant->enableWallet($this->merchantId, self::WALLET);
    }

    protected function makeAndCapturePayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        return $payment;
    }

    public function testPayment()
    {
        $payment = $this->makeAndCapturePayment();

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertNotEmpty($payment['global_token_id']);
        $this->assertNotEmpty($payment['global_customer_id']);
        $this->assertEquals('passed', $payment['two_factor_auth']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testPaymentWithDealerId()
    {
        $directTerminal = $this->fixtures->create('terminal:direct_freecharge_terminal');

        $payment = $this->makeAndCapturePayment();

        $this->assertEquals($directTerminal['id'], $payment['terminal_id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testDebitFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['amount'] = 19999;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $capturePayment = $this->doAuthAndCapturePayment($payment);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testFailedPaymentWalletEntity');
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

        $this->assertEquals($payment['otp_attempts'], 1);

        $this->assertEquals('failed', $payment['two_factor_auth']);

        $this->step = null;
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
            'gateway'       => $this->gateway,
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
            'gateway'       => $this->gateway,
            'contact'       => '9111111111',
            'otp_attempts'  => 2,
            'otp_count'     => 1,
            'terminal_id'   => $this->sharedTerminal->id
        ]);

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'    => $payment->getId(),
            'amount'        => $payment->getAmount(),
            'wallet'        => self::WALLET,
            'reference1'    => '1daea2345',
            'action'        => 'authorize',
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
        $this->testInsufficientBalancePayment();

        $originalData = $this->response->getOriginalContent()->data;

        // Send topup redirect request.
        $response = $this->doWalletTopupViaAjaxRoute($originalData['data']['payment_id']);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, __FUNCTION__);

        return $response;
    }

    public function testTopupAlreadyProcessedPayment()
    {
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
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 50000,
            'contact'       => '9918899029',
            'method'        => 'wallet',
            'wallet'        => self::WALLET,
            'gateway'       => $this->gateway,
            'card_id'       => null,
            'terminal_id'   => $this->sharedTerminal->getId()
        ]);

        $paymentId = $payment->getPublicId();

        $request = $this->testData['topupDataAlreadyProcessed'];

        // Send topup request
        $this->runRequestResponseFlow($request, function() use ($paymentId)
        {
            $this->doWalletTopupViaAjaxRoute($paymentId);
        });
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

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
            'wallet'        => self::WALLET,
            'gateway'       => $this->gateway,
            'card_id'       => null,
            'terminal_id'   => $this->sharedTerminal->id
        ]);

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'          => $payment->getId(),
            'amount'              => $payment->getAmount(),
            'wallet'              => self::WALLET,
            'gateway_merchant_id' => 'random_id',
            'reference1'          => '1daea2345',
            'action'              => 'authorize',
            'status_code'         => 'SUCCESS',
            // Causes the failure, gateway_payment_id is not set if payment
            // failed
            'gateway_payment_id'  => 'daeas',
        ]);


        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testVerifyFailedPaymentOnGatewayFailure()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
            'email'         => 'a@b.com',
            'amount'        => 50000,
            'contact'       => '9918899029',
            'status'        => 'captured',
            'method'        => 'wallet',
            'wallet'        => self::WALLET,
            'gateway'       => $this->gateway,
            'card_id'       => null,
            'terminal_id'   => $this->sharedTerminal->id
        ]);

        // Causes the failure, gateway_payment_id is not set if payment
        // failed
        $wallet = $this->fixtures->create('wallet', [
            'payment_id'          => $payment->getId(),
            'amount'              => $payment->getAmount(),
            'wallet'              => self::WALLET,
            'gateway_merchant_id' => 'random_id',
            'reference1'          => '1daea2345',
            'action'              => 'authorize',
            'status_code'         => 'SUCCESS',
            'received'            => true,
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
        $payment = $this->makeAndCapturePayment();

        $this->refundPayment($payment['id']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertEquals('INITIATED', $wallet['status_code']);

        // Run the cron to verify the refund status and edit gateway refund
        // entity status_code to SUCCESS
        $this->startGatewayRefundValidateCron($this->gateway);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet);
    }

    public function testPartialRefundPayment()
    {
        $payment = $this->makeAndCapturePayment();

        $this->refundPayment($payment['id'], $payment['amount']/2);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertEquals('INITIATED', $wallet['status_code']);

        // Run the cron to verify the refund status and edit gateway refund
        // entity status_code to SUCCESS
        $this->startGatewayRefundValidateCron($this->gateway);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet);
    }

    protected function runPaymentCallbackFlowWalletFreecharge($response, &$callback = null)
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

    public function testCreateRefundRecord()
    {
        $payment = $this->makeAndCapturePayment();

        $refund = $this->refundPayment($payment['id'], 200);

        // delete the refund gateway payment entity
        $this->deleteGatewayRefundEntity($refund['id']);

        $result = $this->startGatewayRefundRecordCron($this->gateway);

        $wallet = $this->getLastEntity('wallet', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(200, $payment['amount_refunded']);
        $this->assertEquals(
            Refund\Entity::verifyIdAndStripSign($refund['id']),
            $wallet['refund_id']);
        $this->assertEquals(1, $result['total_applicable_refunds']);
        $this->assertEquals(1, $result['total_success_refunds']);
    }

    public function testRefundRecordFailed()
    {
        $payment = $this->makeAndCapturePayment();

        $refund = $this->refundPayment($payment['id'], 100);

        // Freecharge failed this refund explicitly after initiating it
        $data = [
            'id'     => 'failedRefund12',
        ];

        $refund = $this->updateRefundEntity($refund['id'], $data);

        $result = $this->startGatewayRefundRecordCron($this->gateway);

        $this->assertEquals(1, $result['total_applicable_refunds']);
        $this->assertEquals(0, $result['total_success_refunds']);
    }

    public function testRefundRecordFailed2()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($capturePayment['id'], 100);

        // When freecharge did not handle the refund request
        // i.e Freecharge does not have any refund transaction for razorpay
        // refund ID
        $data = [
            'id'     => 'failedRefund13',
            'amount' => 300,
        ];

        $refund = $this->updateRefundEntity($refund['id'], $data);

        $result = $this->startGatewayRefundRecordCron($this->gateway);

        $wallet = $this->getLastEntity('wallet', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100, $payment['amount_refunded']);
        $this->assertequals($refund['id'], $wallet['refund_id']);
        $this->assertEquals(1, $result['total_applicable_refunds']);
        $this->assertEquals(1, $result['total_success_refunds']);
    }

    public function testRefundRecordAbsentRefund()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($capturePayment['id'], 100);

        // Freecharge failed this refund explicitly after initiating it
        // refund fails when amount is 100, Changing it to trigger successful refund
        $data = [
            'id'     => 'failedRefund12',
            'amount' => 300,
        ];

        $refund = $this->updateRefundEntity($refund['id'], $data);

        (new Refund\Repository)->saveOrFail($refund);

        $result = $this->startGatewayRefundRecordCron($this->gateway);

        $wallet = $this->getLastEntity('wallet', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100, $payment['amount_refunded']);
        $this->assertequals($refund['id'], $wallet['refund_id']);
        $this->assertEquals(1, $result['total_applicable_refunds']);
        $this->assertEquals(1, $result['total_success_refunds']);
    }

    public function deleteGatewayRefundEntity($id)
    {
        $id = Refund\Entity::verifyIdAndStripSign($id);

        $wallet = $this->walletRepo->findByRefundId($id);

        $this->walletRepo->deleteOrFail($wallet);
    }

    public function testRefundValidationFailed()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($capturePayment['id']);

        // Freecharge failed this refund explicitly after initiating it
        $data['id'] = 'failedRefund12';

        $refund = $this->updateRefundEntity($refund['id'], $data);
        $wallet = $this->getLastEntity('wallet', true);

        // Run the cron to verify the refund status and edit gateway refund
        // entity status_code to SUCCESS
        $result = $this->startGatewayRefundValidateCron($this->gateway);
        $newWallet = $this->getLastEntity('wallet', true);

        $this->assertNotEquals(
            $wallet['gateway_refund_id'],
            $newWallet['gateway_refund_id']);
        $this->assertEquals(1, $result['total_refunds']);
        $this->assertEquals(1, $result['total_failed_refunds']);
        $this->assertEquals(0, $result['total_success_refunds']);
        $this->assertEquals(0, $result['total_unknown_refunds']);
    }

    public function testRefundValidationUnknown()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($capturePayment['id']);

        // Freecharge failed this refund explicitly after initiating it
        $data['id'] = 'initiatedRfnd1';

        $refund = $this->updateRefundEntity($refund['id'], $data);

        // Run the cron to verify the refund status and edit gateway refund
        // entity status_code to SUCCESS
        $result = $this->startGatewayRefundValidateCron($this->gateway);

        $this->assertEquals(1, $result['total_refunds']);
        $this->assertEquals(0, $result['total_failed_refunds']);
        $this->assertEquals(0, $result['total_success_refunds']);
        $this->assertEquals(1, $result['total_unknown_refunds']);
    }

    protected function updateRefundEntity($refundId, $attributes)
    {
        $refund['id'] = Refund\Entity::verifyIdAndSilentlyStripSign($refundId);

        $wallet = $this->walletRepo->findByRefundId($refundId);

        $refund = (new Refund\Repository)->findOrFail($refund['id']);

        // fill does not update id, explicitly update it
        if (isset($attributes['id']) === true)
        {
            $refund['id'] = $attributes['id'];

            if ($wallet !== null)
            {
                $wallet['refund_id'] = $attributes['id'];

                $this->walletRepo->saveOrFail($wallet);
            }
        }

        $refund->fill($attributes);

        (new Refund\Repository)->saveOrFail($refund);

        return $refund;
    }

    public function testApplicationErrorOccurred()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $data = [
                ResponseFields::ERROR_MESSAGE => 'ApplicationErrorOccurred',
                ResponseFields::ERROR_CODE => 'E018',
            ];

            $content = $data;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });

        $this->clearMock();
    }

    protected function clearMock()
    {
        $this->mockServerContentFunction(function(&$input)
        {
        });
    }
}
