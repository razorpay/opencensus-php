<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GooglePayCardTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GooglePayCardTestData.php';

        parent::setUp();

        $this->repo = (new Payment\Repository);
    }

    public function testGooglePayCardCallback()
    {
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', []);

        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');

        $payment->setGateway('cybersource');

        $paymentDataToken = [
            "protocolVersion"   => "ECv1",
            "signedMessage"     => [
                "tag"                => "+x9CGSP0XQZP+eumb4ONpu8jlhhWQSWT/JjZxxKyiMk\u003d",
                "ephemeralPublicKey" => "BJE2922heh1pmcF6aEuGjY1RFcnHwD6kZ/9/WxaEWBAMIuhwqptGh6ynFFBKBgNP/JoQWiTPMyJTFMQuqGuetc4\u003d",
                "encryptedMessage"   => "Pb9+EvKtLd7HS0dsLt7T+rKJvmYpZQf23274/sE18ZMUGPlDEBZvtVyK+lRKW3xxP0/YUqg8BOOyLgAYy6tCQkBdF2wA3leOymT0LoEZ2YoNSrT2YlPH914snR1P86gWYoco5YYBMvMnm8n295wzyUIn22MHVvKBC9cbZRaDoqeip0PgfW7peO0d/tTLbyRooqs7Sf2BvK2tljNbOflMGPEZNKUD6K8myuj92pclZYPIMI9o8C0QsfV6SsFLCK/omDxZ54auLoEORYBcyVDoVBCFr1sJL66JYE7SsMvvT8ACfGfcy7HFVc5rS7qt3iNz9Tac9HNHAsXDJAZjc4AdYfMfEIIa0ytSfEZbD3gHM+xxwhUbCYInOn+KoxxpEuSJDBAVEiLmJH3HodzHN3tJH35avCK2fvkn/ZVGzdYFh/FIDGCFtQfWyuKGm7GHL1cS2wxddhG3qNTG0TK0xasQNCaWmw=="
            ],
            "signature"         => "MEQCID2npCOWMBWTr5hfCzT2cou0UcZou3drDTA8wC3eXi78AiAhJefYECEw6AnyWbpTbOhwXQ1fSEQMiOxXkOJtmrw5sg=="
        ];

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

        $res = $this->makeS2sCallbackAndGetContent($googlePayMessage, 'google_pay');

        $this->assertEquals($res['status'], 'SUCCESS');

        $payment = $this->repo->reload($payment);

        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['iin'], '444433');
        $this->assertEquals($card['network'], 'VISA');
        $this->assertEquals($card['name'], 'dummy card');
    }

    public function testGooglePayCardCallbackDecryptionFailure()
    {
        $order = $this->fixtures->create('order', []);

        $payment = $this->fixtures->create('payment', []);
        $payment['order_id'] = $order['id'];
        $payment->setAuthenticationGateway('google_pay');
        $payment->setGateway('cybersource');

        $paymentDataToken = [
            "protocolVersion"   => "ECv1",
            "signedMessage"     => [
                "tag"                => "+x9CGSP0XQZP+eumb4ONpu8jlhhWQSWT/JjZxxKyiMk\u003d",
                "ephemeralPublicKey" => "BJE2922heh1pmcF6aEuGjY1RFcnHwD6kZ/9/WxaEWBAMIuhwqptGh6ynFFBKBgNP/JoQWiTPMyJTFMQuqGuetc4\u003d",
                "encryptedMessage"   => "Pb9+EvKtLd7HS0dsLt7T+rKJvmYpZQf23274/sE18ZMUGPlDEBZvtVyK+lRKW3xxP0/YUqg8BOOyLgAYy6tCQkBdF2wA3leOymT0LoEZ2YoNSrT2YlPH914snR1P86gWYoco5YYBMvMnm8n295wzyUIn22MHVvKBC9cbZRaDoqeip0PgfW7peO0d/tTLbyRooqs7Sf2BvK2tljNbOflMGPEZNKUD6K8myuj92pclZYPIMI9o8C0QsfV6SsFLCK/omDxZ54auLoEORYBcyVDoVBCFr1sJL66JYE7SsMvvT8ACfGfcy7HFVc5rS7qt3iNz9Tac9HNHAsXDJAZjc4AdYfMfEIIa0ytSfEZbD3gHM+xxwhUbCYInOn+KoxxpEuSJDBAVEiLmJH3HodzHN3tJH35avCK2fvkn/ZVGzdYFh/FIDGCFtQfWyuKGm7GHL1cS2wxddhG3qNTG0TK0xasQNCaWmw=="
            ],
            "signature"         => "MEQCID2npCOWMBWTr5hfCzT2cou0UcZou3drDTA8wC3eXi78AiAhJefYECEw6AnyWbpTbOhwXQ1fSEQMiOxXkOJtmrw5sg==2"
        ];

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment['terminal_id'] = $terminal['id'];
        $this->repo->saveOrFail($payment);

        $request = array(
            'url'     => '/callback/google_pay',
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

        $payment = $this->fixtures->create('payment', []);

        $payment['order_id'] = $order['id'];

        $payment->setAuthenticationGateway('google_pay');

        $payment->setGateway('sharp');

        $paymentDataToken = [
            "protocolVersion"   => "ECv1",
            "signedMessage"     => [
                "tag"                => "+x9CGSP0XQZP+eumb4ONpu8jlhhWQSWT/JjZxxKyiMk\u003d",
                "ephemeralPublicKey" => "BJE2922heh1pmcF6aEuGjY1RFcnHwD6kZ/9/WxaEWBAMIuhwqptGh6ynFFBKBgNP/JoQWiTPMyJTFMQuqGuetc4\u003d",
                "encryptedMessage"   => "Pb9+EvKtLd7HS0dsLt7T+rKJvmYpZQf23274/sE18ZMUGPlDEBZvtVyK+lRKW3xxP0/YUqg8BOOyLgAYy6tCQkBdF2wA3leOymT0LoEZ2YoNSrT2YlPH914snR1P86gWYoco5YYBMvMnm8n295wzyUIn22MHVvKBC9cbZRaDoqeip0PgfW7peO0d/tTLbyRooqs7Sf2BvK2tljNbOflMGPEZNKUD6K8myuj92pclZYPIMI9o8C0QsfV6SsFLCK/omDxZ54auLoEORYBcyVDoVBCFr1sJL66JYE7SsMvvT8ACfGfcy7HFVc5rS7qt3iNz9Tac9HNHAsXDJAZjc4AdYfMfEIIa0ytSfEZbD3gHM+xxwhUbCYInOn+KoxxpEuSJDBAVEiLmJH3HodzHN3tJH35avCK2fvkn/ZVGzdYFh/FIDGCFtQfWyuKGm7GHL1cS2wxddhG3qNTG0TK0xasQNCaWmw=="
            ],
            "signature"         => "MEQCID2npCOWMBWTr5hfCzT2cou0UcZou3drDTA8wC3eXi78AiAhJefYECEw6AnyWbpTbOhwXQ1fSEQMiOxXkOJtmrw5sg=="
        ];

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

        $res = $this->makeS2sCallbackAndGetContent($googlePayMessage, 'google_pay');

        $this->assertEquals($res['status'], 'SUCCESS');

        $payment = $this->repo->reload($payment);

        $this->assertEquals($payment->getStatus(), Payment\Status::AUTHORIZED);
    }
}
