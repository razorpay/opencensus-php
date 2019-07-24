<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Paypal;

use RZP\Gateway\Mozart;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaypalGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const WALLET = 'paypal';

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PaypalGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('1ShrdPhnepeTrm', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');
    }

    public function testRequestTampering()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content['amount'] = 100;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCallbackEmptyResponseBody()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = [];
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNull($wallet);
    }


    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('paypal');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
            PaymentEntity::EMAIL        => 'a@b.com',
            PaymentEntity::AMOUNT       => 50000,
            PaymentEntity::CONTACT      => '+919918899029',
            PaymentEntity::METHOD       => 'wallet',
            PaymentEntity::WALLET       => 'paypal',
            PaymentEntity::GATEWAY      => 'wallet_paypal',
            PaymentEntity::CARD_ID      => null,
            PaymentEntity::TERMINAL_ID  => '1ShrdPhnepeTrm',
        ]);

        $id = $payment->getPublicId();

        $gatewayPayment = $this->fixtures->create('mozart', [
            Mozart\Entity::GATEWAY      => 'wallet_paypal',
            Mozart\Entity::ACTION       => 'authorize',
            Mozart\Entity::AMOUNT       => 50000,
            Mozart\Entity::PAYMENT_ID   => substr($id,4),
            Mozart\Entity::RAW          => '{}',
        ]);

        $this->runRequestResponseFlow($data, function() use ($id)
        {
            $this->verifyPayment($id);
        });

        $mozart = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozart, 'testPaymentMozartEntity');
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
