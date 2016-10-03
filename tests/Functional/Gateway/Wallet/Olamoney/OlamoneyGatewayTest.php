<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Olamoney;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('olamoney');

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
                            PaymentEntity::WALLET       => 'olamoney',
                            PaymentEntity::GATEWAY      => 'wallet_olamoney',
                            PaymentEntity::CARD_ID      => null,
                            PaymentEntity::TERMINAL_ID  => $this->sharedTerminal->id
                        ]);

        $id = $payment->getPublicId();

        $this->runRequestResponseFlow($data, function() use ($id) {
            $this->verifyPayment($id);
        });

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $input = ['amount' => $payment['amount']];

        $authPayment = $this->doAuthPayment($payment);

        $this->refundAuthorizedPayment($authPayment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testAuthPaymentRefund');
    }

    public function testPaymentPartialRefund()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $input = ['amount' => $payment['amount']];

        $payment = $this->doAuthAndCapturePayment($payment);

        $amount = (int) ($payment['amount'] / 3);

        $this->refundPayment($payment['id'], $amount);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund, 'testPaymentPartialRefund');
    }

    public function testFailedPayment()
    {
        $this->failOlamoneyAuthorizePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    protected function failOlamoneyAuthorizePayment()
    {
        $server = $this->mockServerContentFunction(function (& $content)
                        {
                            $content['status'] = 'failed';

                            return $content;
                        });

        $this->makeRequestAndCatchException(
            function ()
            {
                $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

                $content = $this->doAuthPayment($payment);
            });
    }

    protected function runPaymentCallbackFlowWalletOlamoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response     = $response;
        $this->callbackUrl  = $url;

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                return $this->makeOtpCallback($url);
            }

            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackData($request['url'],
                $request['method'], $request['content']);
        }

        return null;
    }

    public function testOlaServerToServerCallback()
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing(function (& $content)
                        {
                            $request = array(
                                'content' => $content,
                                'url' => '/callback/wallet_olamoney',
                                'method' => 'post');

                            // Fire s2s callback request
                            $response = $this->makeRequestAndGetContent($request);

                            $this->assertEquals($response['success'], true);

                            // Stop the progress here.
                            throw new Exception\RuntimeException(
                                'Stop here.');

                        })->mock();

        $this->setMockServer($server);

        try
        {
            $payment = $this->getDefaultWalletPaymentArray(self::WALLET);
            $payment = $this->doAuthPayment($payment);
        }
        catch (Exception\RuntimeException $e)
        {
            ;
        }

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }
}
