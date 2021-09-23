<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GooglePayCardTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GooglePayCardTestData.php';

        parent::setUp();

        $this->repo = (new Payment\Repository);
        $this->terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );
    }

    protected $paymentDataToken = [
        "protocolVersion" => "ECv2",
        "signature" => "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY",
        "intermediateSigningKey" => [
            "signedKey" => "{\"keyValue\":\"MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE/1+3HBVSbdv+j7NaArdgMyoSAM43yRydzqdg1TxodSzA96Dj4Mc1EiKroxxunavVIvdxGnJeFViTzFvzFRxyCw==\",\"keyExpiration\":\"1586519021673\"}",
            "signatures" => [
                "MEQCIDycTORSTIE5z1hr4GWYNEFuJJXdViS5bgkgM06dCi20AiADE8ECbfspxu0ACiW9B9zp1qOKFhk1vrJyh4Ma5ZxPdA=="
            ]
        ],
        "signedMessage" => "{\"encryptedMessage\":\"NzAbUIsB6X9K5ytmDKzZFlosabckSg5xlLLGvwLadVrUC7Wb8+3Sg+7U+Qeuz6G/Zy2WHCB4z+Bj0ViZcTCD4r2boSGsSzxf8tgKLlEyraQZy3Vhu7OiM01/3TRSmH8X/5LTMpQieDYjDTqjTOmhfKWzUzoyBu0v9DUnu5p0HrbLd8WlysmgEKrC0ZqSrx2dLKLPE1q3MxynRtX6xdPhxuKxQ3WUcCPmHA32IWl37vUBdDqF4k1D1t3rBOSv8hiY/dMUXBSUCiqfbDVSD8VgkRYUStfr3I3xx7X/p/nOxTl0Vg957G7h+EwaUX6O6dooQlklV4Q/dxVkTAG/CXC81htwIhMNiMKezLC2tOPsEz8MbmbwmMPwjXHRX5A4mp0tijuLIm3IE3zw1cUipuJfIfR2bVg=\",\"ephemeralPublicKey\":\"BLkD0qenwBG7NYJq90YwDrXgIEs15I525x9UHHQksk7RJiSJ0iJtXHM/6sSEuBrd2o7LH/6KLf0ttx03zW7Mq90=\",\"tag\":\"zeH2NIT1qIFnQDDY2P+TjXvwD2jQaqEhcb6Lrqxx3fI=\"}"
    ];

    public function testGooglePayCardCallbackSuccess()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertGpayAuthorizeResponse($res, $payment);
    }

    public function testGooglePayCardWithUpiTerminalCallbackSuccess()
    {
        $payment = $this->createAndSaveGpayPayment();

        $terminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $payment['terminal_id'] = $terminal['id'];

        $payment['gateway'] = Payment\Gateway::UPI_ICICI;

        $this->repo->saveOrFail($payment);

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertGpayAuthorizeResponse($res, $payment);
    }

    public function testGooglePayCardCallbackFailureSecondTime()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertGpayAuthorizeResponse($res, $payment);

        $this->ba->expressAuth();
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailureAmountMismatch()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);
        $request['content']['amount'] = '13.34';

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailureValidationError()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);
        $request['content']['network'] = 'UNKNOWN';

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailureExtraFieldError()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);
        $request['content']['ThisField'] = 'This field is extra';

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailureMessageExpired()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==3";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailureInvalidCardNumber()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==7";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackFailurePayment()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==2";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );

        $payment = $this->repo->reload($payment);
    }


    public function testGooglePayCardCallbackDecryptionFailure()
    {
        $payment = $this->createAndSaveGpayPayment();

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];
        $this->repo->saveOrFail($payment);

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==4";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackDecryptionRequestFailure()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==5";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackDecryptionIncomplete()
    {
        $payment = $this->createAndSaveGpayPayment();

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==6";
        $request['content']['token'] = $paymentDataToken;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testGooglePayCardCallbackOnSharp()
    {
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', [ 'amount' => 1234 ]);

        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');

        $payment->setGateway('sharp');

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];

        $this->repo->saveOrFail($payment);

        $request = $this->createGpayAuthorizeRequest($payment['id']);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['status'], 'authorized');

        $payment = $this->repo->reload($payment);

        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);
    }

    /**
     * @return array|mixed
     */
    protected function createAndSaveGpayPayment()
    {
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment['terminal_id'] = null;
        $payment['gateway'] = null;
        $payment['method'] = 'unselected';

        $this->repo->saveOrFail($payment);
        return $payment;
    }

    /**
     * @param $id
     * @return array
     */
    protected function createGpayAuthorizeRequest($id): array
    {
        $googlePayMessage = [
            'pgTransactionRefId' => $id,
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'amount'             => '12.34',
            'token'              => $this->paymentDataToken,
            // optional pgBundle
        ];

        $this->ba->expressAuth();
        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => $googlePayMessage,
        );
        return $request;
    }

    /**
     * @param $res
     * @param array $payment
     */
    protected function assertGpayAuthorizeResponse($res, $payment): void
    {
        $this->assertEquals($res['status'], 'authorized');

        // Assert payment entity
        $payment = $this->repo->reload($payment);
        $this->assertEquals(Payment\Status::AUTHORIZED, $payment->getStatus());
        $this->assertEquals(Payment\Method::CARD, $payment->getMethod());
        $this->assertEquals(Payment\Gateway::CYBERSOURCE, $payment->getGateway());
        $this->assertNotNull($payment->getTerminalId());
        $this->assertNotNull($payment->getCardId());

        // Assert card entity
        $card = $this->getLastEntity('card', true);
        $this->assertEquals('444433', $card['iin']);
        $this->assertEquals('VISA', $card['network']);
        $this->assertEquals('dummy card', $card['name']);
    }
}
