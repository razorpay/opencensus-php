<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Gateway\Mozart;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class WalletPayuGatewayTest extends TestCase
{

    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/WalletPayuGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->createTestTerminal();

        $this->fixtures->merchant->enableWallet('10000000000000', 'paytm');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertTestResponse($paymentEntity, 'testPayment');

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');

        return $paymentEntity;
    }

    public function testTamperedAmount()
    {
        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['amount'] = '51234';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testPaymentIdMismatch()
    {
        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['paymentId'] = 'Hacked';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testAuthFailed()
    {
        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthAndCapturePayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->getDefaultWalletPaymentArray('paytm');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailed()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create('payment:failed', [
            PaymentEntity::EMAIL        => 'a@b.com',
            PaymentEntity::AMOUNT       => 50000,
            PaymentEntity::CONTACT      => '+919918899029',
            PaymentEntity::METHOD       => 'wallet',
            PaymentEntity::WALLET       => 'paytm',
            PaymentEntity::GATEWAY      => 'payu',

        ]);

        $id = $payment->getPublicId();

        $this->fixtures->create('mozart', [
            Mozart\Entity::GATEWAY      => 'payu',
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

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['success'] = false;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    protected function runPaymentCallbackFlowPayu($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response,$callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackData($request['url'],$request['method'],$request['content']);
        }

        return null;
    }

    protected function createTestTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:payu_terminal');

        $this->fixtures->merchant->activate();
    }
}
