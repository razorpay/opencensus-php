<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Jiomoney;

use RZP\Http\Route;
use RZP\Gateway\Wallet\Jiomoney\TestAmount;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class JiomoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
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

    public function testPaymentFailureFlow()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $payment['amount'] = ((float) TestAmount::FAIL_PAYMENT_AMOUNT) * 100;

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

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPartialRefundPayment($value='')
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id'], $payment['amount'] / 2);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $payment['amount'] = (((float) TestAmount::FAIL_REFUND_AMOUNT) * 100);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $capturePaymentId = $capturePayment['id'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($capturePaymentId)
        {
            $this->refundPayment($capturePaymentId);
        });

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testRefundFailedPaymentEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    /**
     * Tests the case when transaction data is not found using STATUSQUERY API"
     */
    public function testCheckPaymentStatusApiVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $payment['amount'] = TestAmount::FAIL_STATUSQUERY_AMOUNT;

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

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
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
