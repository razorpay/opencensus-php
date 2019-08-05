<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaypalGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaypalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableInternational();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal');

        $this->payment = $this->getDefaultWalletPaymentArray('paypal');
        $this->payment['currency'] = "USD";

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');
    }

    public function testPayment()
    {
        $payment = $this->payment;

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

        $this->doAuthAndCapturePayment($payment,$payment['amount'],$payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('100000Razorpay', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');
    }

    protected function runPaymentCallbackFlowWalletPaypal($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response = $response;

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackData($request['url'],$request['method'],$request['content']);
        }

        return null;
    }
}
