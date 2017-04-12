<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Mpesa;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class MpesaGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'mpesa';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/MpesaGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mpesa_terminal');

        $this->gateway = 'wallet_mpesa';

        $this->fixtures->merchant->enableWallet('10000000000000', self::WALLET);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);
    }

    public function testPayment()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals($testData, $payment);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');

        $this->assertNotEmpty($wallet['gateway_payment_id']);

        $this->assertNotEmpty($wallet['gateway_payment_id']);
    }

    protected function runPaymentCallbackFlowWalletMpesa($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }

            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackRedirect($url);
        }

        return null;
    }
}
