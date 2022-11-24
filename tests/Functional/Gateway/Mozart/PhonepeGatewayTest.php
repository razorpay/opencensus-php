<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Phonepe;

use RZP\Gateway\Mozart;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PhonepeGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const WALLET = 'phonepe';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PhonepeGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_phonepe_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'phonepe');
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

        $this->markTestSkipped('phonepe wallet normal payment flow has been migrated to nbplus service');
    }

    public function testIntentPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $content = $this->getMockServer()->getAsyncCallbackContentWalletPhonepe($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'wallet_phonepe');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testIntentFailedPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'callback')
            {
                $content['code'] = 'PAYMENT_FAILED';

                $content['success'] = false;
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContentWalletPhonepe($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'wallet_phonepe');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
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

        $this->markTestSkipped('phonepe wallet normal payment flow has been migrated to nbplus service');
    }

    public function testCallbackEmptyResponseBody()
    {
        $this->markTestSkipped('phonepe wallet normal payment flow has been migrated to nbplus service');

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
        $payment = $this->getDefaultWalletPaymentArray('phonepe');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);

        $this->markTestSkipped('phonepe wallet normal payment flow has been migrated to nbplus service');
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
            PaymentEntity::WALLET       => 'phonepe',
            PaymentEntity::GATEWAY      => 'wallet_phonepe',
            PaymentEntity::CARD_ID      => null,
            PaymentEntity::TERMINAL_ID  => '1ShrdPhnepeTrm',
        ]);

        $id = $payment->getPublicId();

        $gatewayPayment = $this->fixtures->create('mozart', [
            Mozart\Entity::GATEWAY      => 'wallet_phonepe',
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

        $this->markTestSkipped('phonepe wallet normal payment flow has been migrated to nbplus service');
    }

    protected function runPaymentCallbackFlowWalletPhonepe($response, &$callback = null)
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
