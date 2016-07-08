<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Olamoney;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Route;

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

        // $this->setMockGatewayFalse();
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    protected function runPaymentCallbackFlowWalletOlamoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;
        $this->callbackUrl  = $url;

        if ($url)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }

            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackData($request['url'], $request['method'], $request['content']);
        }

        return null;
    }
}
