<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Exception;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\FeeBearer;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class CardPaymentServiceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                            function ($mid, $feature, $mode)
                            {
                                return 'on';

                            }));

       $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPaymentViaCpsPayloadCheck()
    {
        $terminal1 = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $terminal2 = $this->fixtures->create('terminal:shared_sharp_terminal');


        $terminals = [$terminal1, $terminal2];

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal1, $terminal2)
            {
                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);
                $this->assertArrayHasKey('terminals', $input);

                $inputTerminal = $input['terminals'][0];

                $this->assertEquals($inputTerminal['id'], $terminal1->getId());
                $this->assertArrayNotHasKey('auth', $inputTerminal);

                $inputTerminal = $input['terminals'][1];

                $this->assertEquals($inputTerminal['id'], $terminal2->getId());
                $this->assertArrayHasKey('auth', $inputTerminal);

                $this->assertEquals('mpi_blade', $inputTerminal['auth']['gateway']);
                $this->assertEquals('_3ds', $inputTerminal['auth']['auth_type']);

                $this->assertSatisfied = true;
            });

        $payment = $this->fixtures->create('payment:status_created');

        $gatewayInput['authentication_terminals'][$terminal2->getId()] = [
            'gateway'   => 'mpi_blade',
            'auth_type' => '_3ds',
        ];

        $this->app['card.payments']->authorizeAcrossTerminals($payment, $gatewayInput, $terminals);

        $this->assertTrue($this->assertSatisfied);

    }

    public function testAuthorizeViaCpsNullResponse()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input)
            {
                return null;
            });

        $this->makeRequestAndCatchException(
        function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        },
        GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment['internal_error_code']);

        $keys = (new Admin\Service)->getConfigKeys();

        // $this->assertEquals(0, $keys[Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED]);
    }

    public function testAuthorizeViaCpsPaymentUpdateInternationalCard()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                return [
                    'data' => null,
                    'payment' => [
                        'auth_type' => null,
                        'terminal_id'  => $terminal->getId(),
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }

    public function testAuthorizeViaCpsPaymentUpdateHandleErrorResponse()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                return [
                    'data' => null,
                    'payment' => [
                        'auth_type' => null,
                        'terminal_id'  => $terminal->getId(),
                    ],
                    'error' => [
                        'internal_error_code'       =>"BAD_REQUEST_PAYMENT_FAILED",
                        'gateway_error_code'        =>"BAD_REQUEST_PAYMENT_FAILED",
                        'gateway_error_description' =>"BAD_REQUEST_PAYMENT_FAILED",
                        'description'               =>"BAD_REQUEST_PAYMENT_FAILED",
                    ],
                ];
            });

        $this->makeRequestAndCatchException(
        function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        },
        GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment['internal_error_code']);
    }

    public function testAuthorizeViaCpsPaymentUpdateHandleErrorResponseServerError()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                return [
                    'data' => null,
                    'payment' => [
                        'auth_type' => null,
                        'terminal_id'  => $terminal->getId(),
                    ],
                    'error' => [
                        'internal_error_code'       =>"SERVER_ERROR",
                        'gateway_error_code'        =>"SERVER_ERROR",
                        'gateway_error_description' =>"SERVER_ERROR",
                        'description'               =>"SERVER_ERROR",
                    ],
                ];
            });

        $this->makeRequestAndCatchException(
        function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        },
        \RZP\Exception\LogicException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('SERVER_ERROR', $payment['error_code']);
        $this->assertEquals('SERVER_ERROR', $payment['internal_error_code']);
    }

    public function testAuthorizeViaCpsPaymentUpdateHandleErrorResponseGatewayError()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                return [
                    'data' => null,
                    'payment' => [
                        'auth_type' => null,
                        'terminal_id'  => $terminal->getId(),
                        'authentication_gateway' => 'mpi_blade',
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_description' =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'description'               =>"GATEWAY_ERROR_UNKNOW_ERROR",
                    ],
                ];
            });

        $this->makeRequestAndCatchException(
        function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        },
        \RZP\Exception\GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);
    }

    public function testAuthorizeViaCpsCheckoutAuthorizePayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->enableCpsConfig();

        $this->mockCps($terminal);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $this->disbaleCpsConfig();
    }

    public function testAuthorizeViaCpsCapturePayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->enableCpsConfig();

        $this->mockCps($terminal);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $this->disbaleCpsConfig();
    }

    public function testAuthorizeViaCpsS2SAuthorize()
    {
        $this->enableCpsConfig();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);
        $this->ba->privateAuth();
        $this->mockCardVault();
        $this->mockCps($terminal);

        $payment = $this->getDefaultPaymentArray();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $this->makeRedirectToAuthorize($targetUrl);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $this->disbaleCpsConfig();
    }

    protected function mockCps($terminal)
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                switch ($url)
                {
                    case 'authorize':
                        $payment = $input['payment'];

                        $content = [
                            'Message' => [
                                'PAReq' => [
                                    'Merchant' => [
                                        'acqBIN' => '11111111111',
                                        'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                                    ],
                                    'CH' => [
                                        'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                                    ],
                                    'Purchase' => [
                                        'xid'    => base64_encode(str_pad($payment['id'], 20, '0', STR_PAD_LEFT)),
                                        'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                        'amount' => '500.00',
                                        'purchAmount' => '50000',
                                        'currency' => '356',
                                        'exponent' => 2,
                                    ]
                                ]
                            ],
                        ];

                        $content['Message']['@attributes']['id'] = $payment['id'];

                        $xml = \Lib\Formatters\Xml::create('ThreeDSecure', $content);

                        $xml = zlib_encode($xml, 15);
                        $xml = base64_encode($xml);

                        return [
                            'data' => [
                                'content' => [
                                    'TermUrl' => $input['callbackUrl'],
                                    'PaReq' => $xml,
                                    'MD' => $payment['id'],
                                ],
                                'method' => 'post',
                                'url' =>  'https://api.razorpay.com/v1/gateway/acs/mpi_blade',
                            ],
                            'payment' => [
                                'terminal_id' => $terminal->getId(),
                                'auth_type' => null,
                                'authentication_gateway' => 'mpi_blade'
                            ],
                        ];

                    case 'action/callback':
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test'
                                ],
                                'two_factor_auth' => 'Y'
                            ],
                            'payment' => [
                                'auth_type' => "3ds",
                            ],
                        ];

                    case 'action/capture':
                        return [
                            'data' => [
                                'status' => 'captured',
                            ],
                        ];
                    default:
                        return null;
                }
            });
    }
}

