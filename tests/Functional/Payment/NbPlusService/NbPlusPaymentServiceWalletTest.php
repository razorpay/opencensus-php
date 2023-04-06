<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Carbon\Carbon;
use Mockery;

use RZP\Error\Error;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbPlusPaymentServiceWalletTest extends TestCase
{
    use PaymentNbplusTrait;

    const WALLET = 'freecharge';
    protected $merchantId = '10000000000000';
    const LOCAL_CUSTOMER = '100000customer';
    const GLOBAL_CUSTOMER = '10000gcustomer';

    const AUTHORIZE_ACTION_INPUT = [
        'wallet',
        'payment',
        'callbackUrl',
        'otpSubmitUrl',
        'payment_analytics',
        'token',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway_config',
    ];

    const DEBIT_INPUT = [
        'payment',
        'token',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway',
        'customer',
        'gateway_config',
    ];

    const CALLBACK_ACTION_INPUT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway_config',
    ];

    const CALLBACK_ACTION_INPUT_DEBIT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'customer',
        'token',
        'gateway_config',
    ];

    const TOPUP_INPUT = [
        'gateway',
        'token',
        'payment',
        'customer',
        'analytics',
        'callbackUrl',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway_config',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $this->gateway = 'wallet_freecharge';

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getTreatment'])
                            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
                  ->method('getTreatment')
                  ->will($this->returnCallback(
                      function ($mid, $feature, $mode)
                      {
                            return 'nbplusps';
                      })
                  );

        $this->terminal = $this->fixtures->create('terminal:shared_freecharge_terminal');

        $this->fixtures->merchant->enableWallet($this->merchantId, self::WALLET);

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Wallet', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->markTestSkipped();
    }

    public function testAuthorize()
    {
        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    if(isset($content['input']['gateway']))
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::DEBIT_INPUT);
                        break;
                    }
                    else
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                        break;
                    }
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->payment['contact'] = "8448720400";

        $this->doAuthPayment($this->payment);

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $this->assertNotNull($token['gateway_token']);

        $this->assertNotNull($token['gateway_token2']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testTopUpFlow()
    {
        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    if(isset($content['input']['gateway']))
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::DEBIT_INPUT);
                        break;
                    }
                    else
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                        break;
                    }
                case NbPlusPaymentService\Action::CALLBACK:
                    if(isset($content['input']['gateway']))
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                        break;
                    }
                    else
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT_DEBIT);
                        break;
                    }
                case NbPlusPaymentService\Action::TOPUP:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::TOPUP_INPUT);
                    break;
            }
        });

        $paymentArray = $this->getDefaultWalletPaymentArray(self::WALLET);

        $paymentArray['contact'] = "8448720430";

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::CREATED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);

        $response = $this->doWalletTopupViaAjaxRoute($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertNotNull($payment['reference1']);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testVerifyPayment()
    {
        $this->payment['contact'] = "8448720400";

        $response = $this->doAuthPayment($this->payment);

        $this->verifyPayment($response['razorpay_payment_id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentVerifyFailed()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::VERIFY)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE     => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE    => [
                            Error::INTERNAL_ERROR_CODE  => 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED',
                        ]
                    ],
                ];
            }
        });

        $this->payment['contact'] = "8448720400";

        $this->doAuthPayment($this->payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $this->makeRequestAndCatchException(
            function() use ($paymentEntity)
            {
                $this->verifyPayment($paymentEntity[Payment\Entity::ID]);
            },
            PaymentVerificationException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPowerWalletPayment()
    {
        $this->setUpWalletToken();

        $this->payment['contact'] = "8448720400";

        $payment = $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);
    }

    public function testOtpResendPayment()
    {
        $this->ba->publicAuth();

        $payment = $this->fixtures->create('payment', [
            'method'        => 'wallet',
            'wallet'        => self::WALLET,
            'gateway'       => $this->gateway,
            'contact'       => '9111111111',
            'otp_attempts'  => 2,
            'otp_count'     => 1,
            'terminal_id'   => $this->terminal->getId(),
            'cps_route'     => 3,
        ]);

        $url = $this->getOtpResendUrl($payment->getPublicId());

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'content' => []
        ];
        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::CREATED, $payment[Payment\Entity::STATUS]);

        $resp = $this->sendRequest($request);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame($payment['otp_attempts'], null);

        $this->assertSame($payment['otp_count'], 2);
    }

    public function testIncorrectOtp()
    {
        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    if(isset($content['input']['gateway']))
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::DEBIT_INPUT);
                        break;
                    }
                    else
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                        break;
                    }
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $paymentArray = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->payment['contact'] = "8448720400";

        $this->setOtp(Otp::INCORRECT);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::CREATED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);

        $this->assertEquals($payment['otp_attempts'], 1);
    }

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        return $this->runPaymentCallbackFlowForFreechargeGateway($response, $callback, $gateway);
    }

    public function runPaymentCallbackFlowForFreechargeGateway($response, & $callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($this->isOtpCallbackUrl($url))
        {
            return $this->makeOtpCallback($url);
        }
        else
        {
            $response = $this->mockCallbackFromGateway($url, $method, $values);

            $data = $this->getPaymentJsonFromCallback($response->getContent());

            $response->setContent($data);

            return $response;
        }
    }

    protected function setUpWalletToken()
    {
        $tokenAttributes = [
            'method'        => 'wallet',
            'wallet'        => Wallet::FREECHARGE,
            'token'         => '101wallettoken',
            'gateway_token' => '8c31d80b-83ed-4f52-8377-71301790ccaa',
            'customer_id'   => self::GLOBAL_CUSTOMER,
            'terminal_id'   => $this->terminal->getId(),
            'created_at'    => Carbon::now()->timestamp,
            'expired_at'    => Carbon::now()->addYear()->timestamp,
        ];

        // Create Token and AppToken
        return $this->fixtures->create('token', $tokenAttributes);
    }
}
