<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mockery;

use RZP\Error\Error;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbPlusPaymentServicePaylaterTest extends TestCase
{
    use PaymentNbplusTrait;

    const AUTHORIZE_ACTION_INPUT = [
        'payment',
        'callbackUrl',
        'otpSubmitUrl',
        'payment_analytics',
        'token',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail'
    ];

    const CALLBACK_ACTION_INPUT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail'
    ];

    const CHECK_ACCOUNT_INPUT = [
        'gateway_data'
    ];


    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $this->gateway = 'paylater';

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

        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['lazypay' => 1]);

        // s2s flow
        $this->terminal = $this->fixtures->create('terminal:paylater_lazypay_terminal');

        // This may be made generic when there are more than just netbanking methods migrated to new service
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Paylater', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultPayLaterPaymentArray($this->bank);

    }

    public function testAuthorizeS2S()
    {
        $this->provider = 'lazypay';

        $splitzMockResponse = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzMockResponse);

        $paymentArray = $this->getDefaultPayLaterPaymentArray($this->provider);

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CHECK_ACCOUNT:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CHECK_ACCOUNT_INPUT);
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testVerify()
    {
        $this->provider = 'lazypay';

        $splitzMockResponse = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzMockResponse);

        $paymentArray = $this->getDefaultPayLaterPaymentArray($this->provider);

        $response = $this->doAuthPayment($paymentArray);

        $this->verifyPayment($response['razorpay_payment_id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }


    public function testPaymentVerifyFailed()
    {
        $this->provider = 'lazypay';

        $splitzMockResponse = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzMockResponse);

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

        $paymentArray = $this->getDefaultPayLaterPaymentArray($this->provider);

        $this->doAuthPayment($paymentArray);

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


    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        return $this->runPaymentCallbackFlowPayLater($response, $callback, $gateway);
    }

    public function runPaymentCallbackFlowPayLater($response, & $callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($this->isOtpVerifyUrl($url) === true)
        {
            $responseData = $this->processCardlessPaymentForm($response);

            $responseInput = json_decode($responseData->getContent(), true);

            $contact = $responseInput['request']['content']['contact'];
            $email   = $responseInput['request']['content']['email'];

            $this->callbackUrl = $url;

            $this->otpFlow = true;

            $response = $this->makeOtpVerifyCallback($url, $email, $contact);
            $responseContent =  json_decode($response->getContent(), true);

            switch ($email)
            {
                case 'invalidott@gmail.com':
                    $responseInput['request']['content']['ott'] = 'invalidott';
                    break;

                default:
                    $responseInput['request']['content']['ott'] = $responseContent['ott'];
            }

            $payment = $responseInput['request']['content'];

            $request = [
                'method'  => 'POST',
                'url'     => $responseInput['payment_create_url'],
                'content' => $payment
            ];

            return $this->makeRequestParent($request);
        }
        elseif ($this->isOtpCallbackUrl($url))
        {
            $this->callbackUrl = $url;

            return $this->makeOtpCallback($url);
        }
        else
        {
            $dt = $this->getFormRequestFromResponse($response->getContent(), $url);

            $resp = $this->sendRequest($dt);

            // array conversion is required because we are getting std class object after json_decode
            $request = [
                'url' => $dt['url'],
                'content' => (array) json_decode(($resp->getContent())),
                'method' => 'POST',
            ];

            $resp = $this->sendRequest($request);

            $data = $this->getPaymentJsonFromCallback($resp->getContent());

            $resp->setContent($data);

            return $resp;
        }
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            Error::INTERNAL_ERROR_CODE  => 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED',
                        ]
                    ],
                ];
            });
    }

}
