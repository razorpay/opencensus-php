<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Jiomoney;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Wallet\Jiomoney\StatusCode;
use RZP\Gateway\Wallet\Jiomoney\TestAmount;
use RZP\Gateway\Wallet\Jiomoney\RequestFields;
use RZP\Gateway\Wallet\Jiomoney\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class JiomoneyGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/JiomoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_jiomoney_terminal');

        $this->gateway = 'wallet_jiomoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'jiomoney');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['amount'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentFailureFlow()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[ResponseFields::STATUS_CODE] = StatusCode::INTERNAL_ERROR;
            $content[ResponseFields::RESPONSE_CODE] = 'FAILED';
            $content[ResponseFields::RESPONSE_DESCRIPTION] = 'NA';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('unknown', $payment['two_factor_auth']);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testFailedPaymentWalletEntity');
    }

    public function testPaymentFailedWithMissingChecksum()
    {
        $this->markTestSkipped('duplicate test');

        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            // unset($content[ResponseFields::CHECKSUM]);
            $content[ResponseFields::STATUS_CODE] = StatusCode::INTERNAL_ERROR;
            $content[ResponseFields::RESPONSE_CODE] = 'FAILED';
            $content[ResponseFields::RESPONSE_DESCRIPTION] = 'BAD_REQUEST';
        });

        $data = $this->testData['testPaymentFailureFlow'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('unknown', $payment['two_factor_auth']);

        $wallet = $this->getLastEntity('wallet', true);

        $expected = $this->testData['testFailedPaymentWalletEntity'];

        $expected['response_description'] = 'BAD_REQUEST';

        $this->assertArraySelectiveEquals($expected, $wallet);
    }

    public function testSuccessfulPaymentWithMissingChecksum()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[ResponseFields::STATUS_CODE] = StatusCode::SUCCESS;
            $content[ResponseFields::RESPONSE_CODE] = 'SUCCESS';
            $content[ResponseFields::RESPONSE_DESCRIPTION] = 'BAD_REQUEST';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSuccessfulPaymentWithTamperedChecksum()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[ResponseFields::STATUS_CODE] = StatusCode::SUCCESS;
            // tamper response code to failed to bypass checksum validation
            $content[ResponseFields::RESPONSE_CODE] = 'FAILED';
            $content[ResponseFields::RESPONSE_DESCRIPTION] = 'BAD_REQUEST';
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS][ResponseFields::TXN_STATUS] = 'error';
            }
            return $content;
        });

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPartialRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refundAmount = $payment['amount'] / 2;

        $this->mockServerContentFunction(function(& $content, $action) use ($refundAmount)
        {
            if ($action === 'validateRefund')
            {
                $actualRefundAmount = (int) ($content[RequestFields::TRANSACTION][RequestFields::AMOUNT] * 100);

                $assertion = ($actualRefundAmount === $refundAmount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });

        $this->refundPayment($capturePayment['id'], $refundAmount);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content[ResponseFields::STATUS_CODE] = StatusCode::INTERNAL_ERROR;
                $content[ResponseFields::RESPONSE_CODE] = 'FAILED';
                $content[ResponseFields::RESPONSE_DESCRIPTION] = 'NA';
            }

            if ($action === 'checkpaymentstatus')
            {
                $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS][ResponseFields::TXN_STATUS] = 'error';
            }
            return $content;
        });

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $capturePaymentId = $capturePayment['id'];

        $refund = $this->refundPayment($capturePaymentId);

        $gatewayRefundEntity = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($gatewayRefundEntity, 'testRefundFailedPaymentEntity');

        $refundEntity = $this->getDbLastRefund();

        $this->assertEquals($refund['id'], 'rfnd_'.$refundEntity['id']);

        $this->assertEquals('created', $refundEntity['status']);

        return $refund;
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyPaymentWhenCheckPaymentStatusApiReturnsArrayOfObjects()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'check_payment_status')
            {
                $checkPaymentStatusData = $content['RESPONSE']['CHECKPAYMENTSTATUS'];

                $content['RESPONSE']['CHECKPAYMENTSTATUS'] = [
                    $checkPaymentStatusData,
                    $checkPaymentStatusData
                ];
            }
        });

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentWithCallbackVerifyStatusFailure()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content['RESPONSE']['CHECKPAYMENTSTATUS']['TXN_STATUS'] = "FAILED";
            }
        });

        $data = $this->testData['testPaymentWithCallbackVerifyStatusFailure'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['status'], "failed");

        $this->assertSame($payment['internal_error_code'], ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
    }

    public function testPaymentWithCallbackVerifyAmountMismatchFailure()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content['RESPONSE']['CHECKPAYMENTSTATUS']['TXN_AMOUNT'] = 49900;
            }
        });

        $data = $this->testData['testPaymentWithCallbackVerifyAmountMismatchFailure'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['status'], "failed");

        $this->assertSame($payment['internal_error_code'], ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED);
    }

    /**
     * Tests the case when transaction data is not found using CHECKPAYMENTSTATUS API
     * and we fallback to STATUSQUERY API for validation"
     */
    public function testStatusQueryApiVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'check_payment_status')
            {
                $content['RESPONSE']['CHECKPAYMENTSTATUS'] = null;
            }
        });

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create(
            'payment:failed',
            [
                'email'         => 'a@b.com',
                'amount'        => 50000,
                'contact'       => '9918899029',
                'method'        => 'wallet',
                'wallet'        => 'jiomoney',
                'gateway'       => 'wallet_jiomoney',
                'card_id'       => null,
                'terminal_id'   => $this->sharedTerminal->id
            ]);

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'          => $payment->getId(),
            'amount'              => $payment->getAmount(),
            'wallet'              => 'jiomoney',
            'action'              => 'authorize',
            'gateway_payment_id'  => null,
            'email'               => 'a@b.com',
            'contact'             => '+919918899029',
            'gateway_merchant_id' => 'random_id',
        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });
    }

    public function testVerifyLateAuthorizedPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create(
            'payment',
            [
                'email'           => 'a@b.com',
                'amount'          => 50000,
                'contact'         => '9918899029',
                'status'          => 'created',
                'method'          => 'wallet',
                'wallet'          => 'jiomoney',
                'gateway'         => 'wallet_jiomoney',
                'card_id'         => null,
                'terminal_id'     => $this->sharedTerminal->id,
                'late_authorized' => 1,
            ]);

        $data = $this->testData[__FUNCTION__];

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'         => $payment->getId(),
            'amount'             => $payment->getAmount(),
            'wallet'             => 'jiomoney',
            'action'             => 'authorize',
            'gateway_payment_id' => null,
        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);
    }

    public function testVerifyRefund()
    {
        $refund = $this->testRefundFailedPayment();

        $this->clearMockFunction();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refundEntity = $this->getDbLastRefund();

        $this->assertEquals($refund['id'], 'rfnd_'.$refundEntity['id']);

        $this->assertEquals(RefundStatus::PROCESSED, $refundEntity['status']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $refund = $this->testRefundFailedPayment();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS][ResponseFields::TXN_STATUS] = 'error';
            }

            if ($action === 'validateRefund')
            {
                unset($content['RESPONSE']['GETREQUESTSTATUS']);

                $content['RESPONSE']['RESPONSE_HEADER']['API_MSG'] = 'TRANSACTION_NOT_FOUND';
            }
        });

        $this->clearMockFunction();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refundEntity = $this->getDbLastRefund();

        $this->assertEquals($refund['id'], 'rfnd_'.$refundEntity['id']);

        $this->assertEquals(RefundStatus::PROCESSED, $refundEntity['status']);
    }

    public function testAuthorizedPaymentRefund()
    {
        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];
        $input = ['amount' => $payment['amount']];

        $this->refundAuthorizedPayment($paymentId, $input);

        $refund = $this->getLastEntity('refund', true);

        $this->assertSame($paymentId, $refund['payment_id']);
        $this->assertTestResponse($refund);
    }

    protected function runPaymentCallbackFlowWalletJiomoney($response, & $callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            // It's a redirect url. Jiomoney use callback flow for payment authorization.
            $request = ['url' => $requestUrl];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
