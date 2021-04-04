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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'UNKNOWN',
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

        $res = $this->makeRequestAndGetContent($request);
        $this->assertEquals($res['status'], 'authorized');

        $payment = $this->repo->reload($payment);
        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['iin'], '444433');
        $this->assertEquals($card['network'], 'VISA');
        $this->assertEquals($card['name'], 'dummy card');
        $this->assertEquals($card['type'], 'UNKNOWN');
    }

    public function testGooglePayCardCallbackFailureSecondTime()
    {
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
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

        $res = $this->makeRequestAndGetContent($request);
        $this->assertEquals($res['status'], 'authorized');

        $payment = $this->repo->reload($payment);
        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);

        $card = $this->getLastEntity('card', true);
        $this->assertEquals($card['iin'], '444433');
        $this->assertEquals($card['network'], 'VISA');
        $this->assertEquals($card['name'], 'dummy card');

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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1334,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'UNKNOWN',
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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'ThisField'          => 'This field is extra',
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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');


        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==3";

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'amount'             => '12.34',
            'token'              => $paymentDataToken,
            // optional pgBundle
        ];

        $this->ba->expressAuth();
        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => $googlePayMessage,
        );

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
        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==7";

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'amount'             => '12.34',
            'token'              => $paymentDataToken,
            // optional pgBundle
        ];

        $this->ba->expressAuth();
        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => $googlePayMessage,
        );

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
        $order = $this->fixtures->create('order', []);
        $payment = $this->fixtures->create('payment', [
            'amount' => 1234,
        ]);
        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $payment['terminal_id'] = $this->terminal['id'];
        $this->repo->saveOrFail($payment);

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==2";

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'amount'             => '12.34',
            'token'              => $paymentDataToken,
            // optional pgBundle
        ];

        $this->ba->expressAuth();
        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => $googlePayMessage,
        );

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );

        $payment = $this->repo->reload($payment);
        $this->assertEquals('GWAZR009', $payment['reference13']);
    }


    public function testGooglePayCardCallbackDecryptionFailure()
    {
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', ['amount' => 1234]);
        $payment['order_id'] = $order['id'];
        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];
        $this->repo->saveOrFail($payment);

        $this->ba->expressAuth();

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==4";

        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => 'pay_' . $payment['id'],
                'cardType'           => 'CREDIT',
                'network'            => 'VISA',
                'amount'             => '12.34',
                'token'              => $paymentDataToken,
            ],
        );

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
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', ['amount' => 1234]);
        $payment['order_id'] = $order['id'];
        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];
        $this->repo->saveOrFail($payment);

        $this->ba->expressAuth();

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==5";

        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => 'pay_' . $payment['id'],
                'cardType'           => 'CREDIT',
                'network'            => 'VISA',
                'amount'             => '12.34',
                'token'              => $paymentDataToken,
            ],
        );

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
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', ['amount' => 1234]);
        $payment['order_id'] = $order['id'];
        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];
        $this->repo->saveOrFail($payment);

        $this->ba->expressAuth();

        $paymentDataToken = $this->paymentDataToken;
        $paymentDataToken['signature'] = "MEYCIQDVSnPca+hhBAtksD3mLOVrOaCr30Sd0VAFBpQdiCSboAIhAI5U+rQPCIpP7ouvEfoH15omHhN7znRHASDqV2HdOQCY==6";

        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => 'pay_' . $payment['id'],
                'cardType'           => 'CREDIT',
                'network'            => 'VISA',
                'amount'             => '12.34',
                'token'              => $paymentDataToken,
            ],
        );

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

        $paymentDataToken = $this->paymentDataToken;

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];

        $this->repo->saveOrFail($payment);

        $googlePayMessage = [
            'pgTransactionRefId' => 'pay_' . $payment['id'],
            'cardType'           => 'CREDIT',
            'network'            => 'VISA',
            'amount'             => '12.34',
            'token'              => $paymentDataToken,
            // optional pgBundle
        ];

        $this->ba->expressAuth();
        $request = array(
            'url'     => '/gateway/google_pay/authorize',
            'method'  => 'post',
            'content' => $googlePayMessage,
        );

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['status'], 'authorized');

        $payment = $this->repo->reload($payment);

        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);
    }
}
