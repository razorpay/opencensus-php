<?php

namespace Tests\Functional\Gateway\Wallet\Olamoney;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;
use Http\Route;

class OlamoneyGatewayTest extends TestCase
{
    use PaymentTrait;

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

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('olamoney');

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
        $this->otpSubmitUrl = $url;

        if (true)
        {
            $this->setOtp('111111');

            if (isset($this->step))
            {
                switch ($this->step)
                {
                    case 'RETRY':
                        $this->setOtp('121212');
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
