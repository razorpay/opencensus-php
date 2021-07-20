<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Wallet\Sbibuddy\StatusCode;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseCodeMap;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SbibuddyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SbibuddyGatewayTestData.php';

        parent::setUp();

        $this->markTestSkipped();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sbibuddy_terminal');

        $this->gateway = 'wallet_sbibuddy';

        $this->fixtures->merchant->enableWallet('10000000000000', 'sbibuddy');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testAmountTampering()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content[ResponseFields::AMOUNT] = "100.00";
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentFailure()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if($action === 'authorize')
            {
                $content[ResponseFields::STATUS_CODE] = ResponseCodeMap::GENERAL_ERROR;
                $content[ResponseFields::ERROR_DESCRIPTION] = 'Error occured';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('unknown', $payment[Payment::TWO_FACTOR_AUTH]);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testFailedPaymentWalletEntity');
    }

    public function testInsufficientFundsPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            $content[ResponseFields::STATUS_CODE] = ResponseCodeMap::INSUFFICIENT_BALANCE;

            $content[ResponseFields::ERROR_DESCRIPTION] = ResponseCodeMap::$codes[
                ResponseCodeMap::INSUFFICIENT_BALANCE
            ];

            unset($content[ResponseFields::EXTERNAL_TRANSACTION_ID]);
            unset($content[ResponseFields::PROCESSOR_ID]);
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPartialRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $refundAmount = $payment['amount'] / 2;

        $this->mockServerContentFunction(function(& $content, $action) use ($refundAmount)
        {
            if ($action === 'validateRefund')
            {
                $actualRefundAmount = (int) ($content['amount'] * 100);

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
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content[ResponseFields::STATUS_CODE] = ResponseCodeMap::GENERAL_ERROR;

                $content[ResponseFields::ERROR_DESCRIPTION] = 'An error occured';
            }
        });

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $capturePaymentId = $capturePayment['id'];

        $refund = $this->refundPayment($capturePaymentId);

        $gatewayRefundEntity = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($gatewayRefundEntity, 'testRefundFailedPaymentEntity');

        return $refund;
    }

    public function testVerifyRefund()
    {
        $refund = $this->testRefundFailedPayment();

        // If refund failed and if on retry the response is duplicate transaction,
        // it means the refund was initially successfull at gateway
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content[ResponseFields::STATUS_CODE] = ResponseCodeMap::DUPLICATE_TRANSACTION;

                $content[ResponseFields::ERROR_DESCRIPTION] = 'Duplicate transaction';
            }
        });

        $response = $this->retryFailedRefund($refund['id']);

        $this->assertEquals(RefundStatus::PROCESSED, $response['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testAmountMismatchVerifyFailure()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $authPayment = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::AMOUNT] = '1000.00';
                }
            }
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($authPayment)
            {
                $this->verifyPayment($authPayment['razorpay_payment_id']);
            }
        );
    }

    public function testAuthFailedVerifySuccessPayment()
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
                'wallet'        => 'sbibuddy',
                'gateway'       => 'wallet_sbibuddy',
                'card_id'       => null,
                'terminal_id'   => $this->sharedTerminal->id
            ]);

        $wallet = $this->fixtures->create('wallet', [
            'payment_id'          => $payment->getId(),
            'amount'              => $payment->getAmount(),
            'wallet'              => 'sbibuddy',
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

    public function testAuthFailedVerifyFailurePayment()
    {
        $this->testPaymentFailure();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content[ResponseFields::AMOUNT] = '';

                    $content[ResponseFields::STATUS_CODE] = ResponseCodeMap::INSUFFICIENT_BALANCE;
                }
            }
        );

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    //----------------------Helper methods-----------------------

    protected function runPaymentCallbackFlowWalletSbibuddy($response, & $callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $mock = $this->isGatewayMocked();

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            $request = ['url' => $requestUrl];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
