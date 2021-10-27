<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use Requests_Response;
use RZP\Exception;
use RZP\Models\Address\Repository;
use RZP\Models\Address\Type;
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
use RZP\Services\CardPaymentService;
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

    protected $razorxValue = 'on';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CardPaymentServiceTestData.php';

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
                                return $this->razorxValue;

                            }) );

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
                $input = $input['input'];
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
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test12',
                        ],
                    ],
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

        $this->mockCps($terminal, "auth_across_terminal_mock");

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

    public function testCardPaymentServiceUnauthorizedAccess()
    {
        $this->razorxValue = 'cardps';
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->mockCpsUnauthorizedAccess();

        $paymentArray = $this->getDefaultPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            \RZP\Exception\ServerErrorException::class);

        $this->disbaleCpsConfig();
    }

    public function testVerifyError()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->enableCpsConfig();

        $this->mockCps($terminal, 'headless_fatal_mock');

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            \RZP\Exception\GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);

        $this->mockCpsErrorVerify($terminal, 'verify_error');

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
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

        $this->mockCps($terminal, "auth_across_terminal_mock");

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
        $this->mockCps($terminal, "auth_across_terminal_mock");

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

    public function testAuthorizationWithHeadlessViaCps()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $this->enableCpsConfig();

        $this->mockCps($terminal, 'headless_mock');

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('headless_otp', $payment['auth_type']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $this->disbaleCpsConfig();
        $this->razorxValue = "on";
    }

    public function testHeadlessFatalErrorInCpsResponse()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'magic'        => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 556763, ['flows' => $flows]);

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '5567630000002004';

        $this->mockCps($terminal, 'headless_fatal_mock');

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

        $iin = $this->getEntityById('iin', 556763, true);
        self::assertNotContains('headless_otp', $iin['flows']);


        $this->razorxValue = "on";
    }

    public function testHeadlessIncorrectOtp()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            'magic'        => '1',
            'iframe'       => '1',
        ];

        $this->fixtures->edit('iin', 556763, ['flows' => $flows]);

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '5567630000002004';

        $this->mockCps($terminal, 'headless_incorrect_otp');

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            \RZP\Exception\BadRequestException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('created', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('headless_otp', $payment['auth_type']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_OTP_INCORRECT', $payment['internal_error_code']);

        $this->razorxValue = "on";
    }

    public function testCallbackSplitAuthenticatePayment()
    {
        $this->razorxValue = 'cardps';
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);
        $this->fixtures->merchant->addFeatures(['auth_split']);
        $this->mockCps($terminal, 'callback_split');

        $paymentArray = $this->getDefaultPaymentArray();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals('3ds', $payment['auth_type']);

        $this->disbaleCpsConfig();
    }

    public function testNotEnrolledImaliPayment()
    {
        $this->razorxValue = 'cardps';
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['auth_split']);
        $this->mockCps($terminal, 'not_enrolled_auth_split');

        $paymentArray = $this->getDefaultPaymentArray();

        $res = $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals($payment['public_id'], $res['razorpay_payment_id']);
        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->disbaleCpsConfig();
    }

    public function testIvr3dsFallback()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['ivr']);

        $this->mockCardVault();


        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'ivr' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '5567630000002004';

        $this->enableCpsConfig();

        $this->mockCps($terminal, 'ivr_fallback_mock');

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);

        $iin = $this->getEntityById('iin', 556763, true);
        self::assertEquals('3ds',$payment['auth_type']);
        self::assertNotContains('ivr', $iin['flows']);


        $this->razorxValue = "on";
    }

     public function testIvr3dsFallbackJson()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);


        $this->mockCardVault();


        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'ivr' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '5567630000002004';

        $this->enableCpsConfig();

        $this->mockCps($terminal, 'ivr_fallback_mock');

         $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $paymentArray
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $content =$this->getJsonContentFromResponse($response);
        $content =$this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertArrayHasKey('next', $content);

        $this->assertArrayHasKey('action', $content['next'][0]);

        $this->assertArrayHasKey('url', $content['next'][0]);

        $redirectContent = $content['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $response = $this->makeRedirectToAuthorize($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['id'], $content['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->razorxValue = "on";
    }

    public function testPaymentAuthorized()
    {
        $this->markTestSkipped();

        $this->razorxValue = "cardps";

        $this->enableCpsConfig();

        $this->mockCps(null, 'auth_across_terminal_mock');

        $this->fixtures->create('payment:card_authenticated');

        $this->fixtures->merchant->addFeatures(['auth_split']);

        $this->mockCardVault();

        $payment = $this->getLastEntity('payment', true);

        $this->ba->expressAuth();

        $request = [
            "url" => "/payments/" . $payment['id'] . "/authorize",
            "method" => "post",
            "content" => [
                "meta" => [
                    "action_type"  => "capture",
                    "reference_id" => $payment['id']
                ],
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertNotNull($payment['authorized_at']);

        $this->assertNotNull($payment['captured_at']);

        $this->ba->expressAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/meta/reference',
            'content' => [
                 'action_type'       => 'capture',
                 'reference_id' => $payment['id'],
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($payment['id'], 'pay_' . $response['payment_id']);

        $this->mockCps(null, 'pay_error');

        $this->fixtures->create('payment:card_authenticated');

        $this->mockCardVault();

        $payment = $this->getLastEntity('payment', true);

        $this->ba->expressAuth();

        $request = [
            "url" => "/payments/" . $payment['id'] . "/authorize",
            "method" => "post",
            "content" => [
                "meta" => [
                    "action_type"  => "capture",
                    "reference_id" => $payment['id']
                ],
            ],
        ];

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        },
        GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/meta/reference',
            'content' => [
                 'action_type'       => 'capture',
                 'reference_id' => $payment['id'],
            ]
        ];

        $this->ba->expressAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($payment['id'], 'pay_' . $response['payment_id']);


        $this->mockCps(null, 'capture_error');

        $this->fixtures->create('payment:card_authenticated');

        $this->mockCardVault();

        $payment = $this->getLastEntity('payment', true);

        $this->ba->expressAuth();

        $request = [
            "url" => "/payments/" . $payment['id'] . "/authorize",
            "method" => "post",
            "content" => [
                "meta" => [
                    "action_type"  => "capture",
                    "reference_id" => $payment['id']
                ],
            ],
        ];

        $this->makeRequestAndCatchException(
        function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        },
        BadRequestException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertNull($payment['captured_at']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/meta/reference',
            'content' => [
                 'action_type'       => 'capture',
                 'reference_id' => $payment['id'],
            ]
        ];

        $this->ba->expressAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($payment['id'], 'pay_' . $response['payment_id']);
    }

    public function testAuthorizeWithCallbackSplit()
    {
        $this->razorxValue = 'cardps';
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);
        $this->mockCps($terminal, 'callback_split');

        $paymentArray = $this->getDefaultPaymentArray();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals('3ds', $payment['auth_type']);
        $this->assertEquals('test', $payment['reference2']);
        $this->assertEquals('Y', $payment['two_factor_auth']);

        // Failure Case
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds' => '1',
            ]
        ]);
        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '5567630000002004';

        $this->mockCps($terminal, 'callback_split');

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            BadRequestException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE', $payment['internal_error_code']);

        $this->disbaleCpsConfig();
    }

    public function testDccPaymentViaCpsNewAuthorizeParamsCheck()
    {
        $this->enableCpsConfig();

        $this->fixtures->merchant->addFeatures(['dcc']);
        $this->fixtures->merchant->addFeatures(['send_dcc_compliance']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $paymentArray) {
                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);

                $input = $input['input'];
                $inputPayment = $input['payment'];
                $this->assertEquals(false, $inputPayment['dcc']);
                $this->assertEquals($paymentArray['amount'], $inputPayment['merchant_pay_amount']);

                $this->assertSatisfied = true;

                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ],
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testDccPaymentViaCpsNewAuthorizeParamsCheckWithoutShowComplianceFlag()
    {
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $paymentArray) {
                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);

                $input = $input['input'];
                $inputPayment = $input['payment'];

                $this->assertEquals(false, array_key_exists('dcc', $inputPayment));
                $this->assertEquals(false, array_key_exists('merchant_pay_amount', $inputPayment));

                $this->assertSatisfied = true;

                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ],
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWithAVSBillingAddressParam()
    {
        $this->enableCpsConfig();

        $this->fixtures->merchant->addFeatures(['avs']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getAVSPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $paymentArray) {

                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);
                $this->assertEquals($paymentArray['billing_address'], $input['input']['payment']['billing_address']);

                $this->assertSatisfied = true;

                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ],
                        'avs_result' => 'B'
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWithoutAVSBillingAddressParam()
    {
        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $paymentArray) {

                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);
                $this->assertArrayNotHasKey('billing_address', $input['input']['payment']);

                $this->assertSatisfied = true;

                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ]
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testFetchAuthenticationEntity()
    {
        $this->mockCps(null, 'entity_fetch');

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/payments/authentication/pay_Flj85rfBFlPfVu',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Flj85rfBFlPfVu', $response['payment_id']);
        $this->assertEquals('CCOhinUeUsT8HN', $response['merchant_id']);
        $this->assertEquals('05', $response['eci']);
    }

    public function testFetchAuthorizationEntity()
    {
        $this->mockCps(null, 'entity_fetch');

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/payments/authorization/pay_Flj85rfBFlPfVu',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('Flj85rfBFlPfVu', $response['payment_id']);
        $this->assertEquals('CCOhinUeUsT8HN', $response['merchant_id']);
        $this->assertEquals('052128', $response['auth_code']);
    }

    public function testFetchAuthenticationEntityFailure()
    {
        $this->mockCps(null, 'entity_fetch');

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/payments/authentication/pay_Flj85rfBFlPfV2',
            'method'  => 'get',
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

    protected function mockCpsEntityFetch($url)
    {
        switch ($url)
        {
            case 'entity/authentication/Flj85rfBFlPfVu':
                return [
                    'id' => 'Flj87LBAuB6JcE',
                    'created_at' => 1602011616,
                    'payment_id' => 'Flj85rfBFlPfVu',
                    'merchant_id' => 'CCOhinUeUsT8HN',
                    'attempt_id' => 'Flj87KPgVIXUjX',
                    'status' => 'skip',
                    'gateway' => 'visasafeclick',
                    'terminal_id' => 'DfqXJH6OO9NEU5',
                    'gateway_merchant_id' => 'escowrazcybs',
                    'enrollment_status' => 'Y',
                    'pares_status' => 'Y',
                    'acs_url' => '',
                    'eci' => '05',
                    'commerce_indicator' => '',
                    'xid' => 'ODUzNTYzOTcwODU5NzY3Qw==',
                    'cavv' => '3q2+78r+ur7erb7vyv66vv\\/\\/8=',
                    'cavv_algorithm' => '1',
                    'notes' => '',
                    'error_code' => '',
                    'gateway_error_code' => '',
                    'gateway_error_description' => '',
                    'gateway_transaction_id1' => '',
                    'gateway_reference_id1' => '',
                    'success' => true
                ];
            case 'entity/authorization/Flj85rfBFlPfVu':
                return [
                    'id' => 'Flj87MbKrlsztd',
                    'created_at' => 1602011616,
                    'merchant_id' => 'CCOhinUeUsT8HN',
                    'payment_id' => 'Flj85rfBFlPfVu',
                    'verify_id' => 'Flj85rfBFlPfVu',
                    'recon_id' => '',
                    'acquirer' => 'hdfc',
                    'gateway' => 'cybersource',
                    'gateway_merchant_id' => 'escowrazcybs',
                    'action' => 'authorize',
                    'amount' => 100,
                    'currency' => 'INR',
                    'gateway_transaction_id' => 'Flj87MVvqSonRp',
                    'gateway_reference_id1' => '6020116178806361104007',
                    'cavv_algorithm' => '',
                    'status' => 'failed',
                    'notes' => '',
                    'auth_code' => '052128',
                    'rrn' => '',
                    'arn' => '',
                    'avs_response_code' => '',
                    'cvc_response_code' => '',
                    'risk_result' => '',
                    'switch_response_code' => '',
                    'error_code' => 'SERVER_ERROR_INVALID_ARGUMENT',
                    'gateway_error_code' => '102',
                    'gateway_error_description' => 'One or more fields in the request contains invalid data',
                    'acs_transaction_id' => '',
                    'gateway_payment_id' => '',
                    'success' => true
                ];
            default:
                return [
                    'error' => 'CORE_FAILED_TO_FIND_MODEL',
                    'success' => false,
                ];
        }
    }

    protected function mockCpsHeadlessAuthError(string $method, string $url, array $input)
    {
        switch($url)
        {
            case 'action/authorize':
                return [
                    'data' => null,
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_description' =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'description'               =>"GATEWAY_ERROR_UNKNOW_ERROR",
                    ],
                    'headless' => [
                        'disable_iin'   => true,
                    ]
                ];
        }
    }

    protected function mockCpsHeadlessFlow(string $method, string $url, array $input, $terminal)
    {
        switch ($url)
        {
            case 'action/authorize':

                $payment = $this->getDbLastPayment();

                return [
                    'data' => [
                        'content' => [
                            'bank' => 'RZP',
                            'type' => 'otp',
                            'next' => [
                                'submit_otp',
                                'resend_otp'
                            ],
                        ],
                        'method' => 'POST',
                        'url' => $this->getOtpSubmitUrl($payment),
                    ],
                    'payment' => [
                        'auth_type' => "headless_otp",
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
                        'auth_type' => "headless_otp",
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

    }

    protected function mockCpsHeadlessIncorrectOtp(string $method, string $url)
    {
        switch ($url)
        {
            case 'action/authorize':

                $payment = $this->getDbLastPayment();

                return [
                    'data' => [
                        'content' => [
                            'bank' => 'RZP',
                            'type' => 'otp',
                            'next' => [
                                'submit_otp',
                                'resend_otp'
                            ],
                        ],
                        'method' => 'POST',
                        'url' => $this->getOtpSubmitUrl($payment),
                    ],
                    'payment' => [
                        'auth_type' => "headless_otp",
                    ],
                ];

            case 'action/callback':
                return [
                    'data' => [
                        'next' => [
                            'submit_otp',
                            'resend_otp'
                        ]
                    ],
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       =>"BAD_REQUEST_PAYMENT_OTP_INCORRECT",
                        'gateway_error_code'        =>"",
                        'gateway_error_description' =>"",
                        'description'               =>"BAD_REQUEST_PAYMENT_OTP_INCORRECT",
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

    }

    protected function mockCpsAuthorizeAcrossTerminals(string $method, string $url, array $input, $terminal)
    {
        $input = $input['input'];
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

            case 'action/pay':
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

            default:
                return null;
        }
    }

    protected function mockCpsCaptureGatewayError(string $method, string $url, array $input, $terminal)
    {
        $input = $input['input'];
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
                    'data' => null,
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_description' =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'description'               =>"GATEWAY_ERROR_UNKNOW_ERROR",
                    ],
                    'headless' => [
                        'disable_iin'   => true,
                    ]
                ];

            case 'action/pay':
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

            default:
                return null;
        }
    }

    protected function mockCpsIvrFallback(string $method, string $url, array $input)
    {
        $input = $input['input'];
        switch ($url) {

            case 'action/authorize':

                $payment = $this->getDbLastPayment();

                return [
                    'data' => [
                        "content" => [
                            "MD" => $payment->getId(),
                            "PaReq" => "eJxcUt1u2jAUvucprNwvjt0fKDpxFQpsuWBru6GK3kyucwapgpM6zgZc7q32OnuSyoFgaKRI5/tRzsn5Dtxu1gX5jabOSx0HLIwCglqVWa6XcdDYX58GAamt1JksSo1xoMvgVvTgx8ogjr+jagyKHiEww7qWSyR5FgeV3P6cJNPdUzGYyvy1KeaB8xAC98kjvu1rQuDQVrAwCjnQDnbyDI1aSW07ghCQ6m2UfhWXjEX9K6AH6PU1mnQsjNyVppJboHvsdS3XKFKtDGb5S4FAW8Lrqmy0NVtxcXUNtANebkwhVtZWQ0rZDQ/Z9SBkYZ8DdUI3Nv04N9w3jqhPG23yTMzGyR/3Ps6nn7HI6m+T55V8qtjLdB4DdQ7vz6RFwSMeRYzfEBYNOR8yDrTlT/azdjOL/3//ERb2GdAD4R2VmyXZs8w5TomTRTTGoFbdJjrkDbipSo3aCg70WB9X8PGP4e7LWYrKpmORjB6eVTJ5HT0syrt0sUsWy+TwxC7b1nTWMTdbwS+iy7Zl7qMB2n0f6PHEXBDtTYoe0PN7fQ8AAP//9qvT/g==",
                            "TermUrl" => $input['callbackUrl']
                        ],
                        "method" => "post",
                        "url" => "https://api.razorpay.com/v1/gateway/acs/mpi_blade"
                    ],
                    "error" => [
                        "internal_error_code" => "GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE",
                        "gateway_error_code" => "",
                        "gateway_error_description" => "",
                        "description" => "IVR Authentication not available"
                    ],
                    "ivr" => [
                        "disable_iin"=> true,
                    ],
                    "success" => true,
                    'payment' => [
                        'auth_type' => "3ds",
                    ],
                ];
            case "action/callback" :
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

            default :
                return null;
        }
    }

    protected function mockCpsPayGatewayError(string $method, string $url, array $input)
    {
        switch ($url) {

            case 'action/pay':

                $payment = $this->getDbLastPayment();

               return [
                    'data' => null,
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_description' =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'description'               =>"GATEWAY_ERROR_UNKNOW_ERROR",
                    ],
                    'headless' => [
                        'disable_iin'   => true,
                    ]
                ];
            default :
                return null;
        }
    }

    protected function mockCpsCallbackSplit($method, $url, $input, $terminal)
    {
        $input = $input['input'];
        switch ($url) {
            case 'action/authorize':
                $payment = $this->getDbLastPayment();
                return [
                    'data' => [
                        "content" => [
                            "MD" => $payment->getId(),
                            "PaReq" => "eJxcUt1u2jAUvucprNwvjt0fKDpxFQpsuWBru6GK3kyucwapgpM6zgZc7q32OnuSyoFgaKRI5/tRzsn5Dtxu1gX5jabOSx0HLIwCglqVWa6XcdDYX58GAamt1JksSo1xoMvgVvTgx8ogjr+jagyKHiEww7qWSyR5FgeV3P6cJNPdUzGYyvy1KeaB8xAC98kjvu1rQuDQVrAwCjnQDnbyDI1aSW07ghCQ6m2UfhWXjEX9K6AH6PU1mnQsjNyVppJboHvsdS3XKFKtDGb5S4FAW8Lrqmy0NVtxcXUNtANebkwhVtZWQ0rZDQ/Z9SBkYZ8DdUI3Nv04N9w3jqhPG23yTMzGyR/3Ps6nn7HI6m+T55V8qtjLdB4DdQ7vz6RFwSMeRYzfEBYNOR8yDrTlT/azdjOL/3//ERb2GdAD4R2VmyXZs8w5TomTRTTGoFbdJjrkDbipSo3aCg70WB9X8PGP4e7LWYrKpmORjB6eVTJ5HT0syrt0sUsWy+TwxC7b1nTWMTdbwS+iy7Zl7qMB2n0f6PHEXBDtTYoe0PN7fQ8AAP//9qvT/g==",
                            "TermUrl" => $input['callbackUrl']
                        ],
                        "method" => "post",
                        "url" => "https://api.razorpay.com/v1/gateway/acs/mpi_blade"
                    ],
                    "error" => [],
                    "ivr" => [],
                    "success" => true,
                    'payment' => [
                        'auth_type' => "3ds",
                    ],
                ];
            case 'action/callback' :
                return [
                    'data' => [
                        'status' => 'authenticated',
                    ],
                ];
            case 'action/pay':
                if ((isset($input['iin']['iin']) === true) and ($input['iin']['iin'] === '556763'))
                {
                    return [
                        'data'  => null,
                        'error' => [
                            'internal_error_code'       => 'BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE',
                            'gateway_error_code'        => '',
                            'gateway_error_description' => '',
                            'description'               => 'Not sufficient funds'
                        ],
                        'payment' => [],
                        'success' => false,
                    ];
                }
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
            default:
                return null;
        }
    }


    private function mockCpsNotEnrolledSplit(string $url, array $input, $terminal)
    {
        $input = $input['input'];
        switch ($url) {
            case 'action/authorize':
                return [
                    'data' => [
                        'status' => 'authenticated',
                    ],
                ];
            case 'action/callback' :
                return [
                    'data' => [
                        'status' => 'authenticated',
                    ],
                ];
            case 'action/pay':
                if ((isset($input['iin']['iin']) === true) and ($input['iin']['iin'] === '556763')) {
                    return [
                        'data' => null,
                        'error' => [
                            'internal_error_code' => 'BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE',
                            'gateway_error_code' => '',
                            'gateway_error_description' => '',
                            'description' => 'Not sufficient funds'
                        ],
                        'payment' => [],
                        'success' => false,
                    ];
                }
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
            default:
                return null;
        }
    }

    protected function mockCpsEmptyAuthCode($url, $input, $terminal)
    {
        $input = $input['input'];
        switch ($url)
        {
            case 'action/authorize':
                $payment = $input['payment'];
                return [
                    'data' => [
                        'content' => [
                            'TermUrl' => $input['callbackUrl'],
                            'PaReq' => "eJxcUt1u2jAUvucprNwvjt0fKDpxFQpsuWBru6GK3kyucwapgpM6zgZc7q32OnuSyoFgaKRI5/tRzsn5Dtxu1gX5jabOSx0HLIwCglqVWa6XcdDYX58GAamt1JksSo1xoMvgVvTgx8ogjr+jagyKHiEww7qWSyR5FgeV3P6cJNPdUzGYyvy1KeaB8xAC98kjvu1rQuDQVrAwCjnQDnbyDI1aSW07ghCQ6m2UfhWXjEX9K6AH6PU1mnQsjNyVppJboHvsdS3XKFKtDGb5S4FAW8Lrqmy0NVtxcXUNtANebkwhVtZWQ0rZDQ/Z9SBkYZ8DdUI3Nv04N9w3jqhPG23yTMzGyR/3Ps6nn7HI6m+T55V8qtjLdB4DdQ7vz6RFwSMeRYzfEBYNOR8yDrTlT/azdjOL/3//ERb2GdAD4R2VmyXZs8w5TomTRTTGoFbdJjrkDbipSo3aCg70WB9X8PGP4e7LWYrKpmORjB6eVTJ5HT0syrt0sUsWy+TwxC7b1nTWMTdbwS+iy7Zl7qMB2n0f6PHEXBDtTYoe0PN7fQ8AAP//9qvT/g==",
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
                        'acquirer' => [],
                        'two_factor_auth' => 'Y'
                    ],
                    'payment' => [
                        'auth_type' => "3ds",
                    ],
                ];

            default:
                return null;
        }

    }

    protected function mockCpsUnauthorizedAccess()
    {
        $cardService = $this->getMockBuilder(CardPaymentService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRawRequest'])
            ->getMock();
        $this->app->instance('card.payments', $cardService);
        $response = new Requests_Response();
        $response->status_code = 401;
        $response->headers = ['Content-Type' => 'application/json'];
        $body = '{
                  "success": false,
                  "error": "Unauthorized: Invalid Username or Password"
                }';
        $response->body = $body;
        $this->app['card.payments']->method('sendRawRequest')->willReturn($response);
    }

    protected function mockCpsErrorVerify($terminal, $responder)
    {
        $cardService = $this->getMockBuilder(CardPaymentService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRawRequest'])
            ->getMock();
        $this->app->instance('card.payments', $cardService);
        $response = new Requests_Response();
        $response->status_code = 400;
        $response->headers = ['Content-Type' => 'application/json'];
        $body = '{
                  "payment": [],
                  "data": [],
                  "headless": [],
                  "ivr" :[],
                  "success": false,
                  "error": {
                    "internal_error_code": "BAD_REQUEST_PAYMENT_FAILED",
                    "gateway_error_code": "BAD_REQUEST_PAYMENT_FAILED",
                    "description": "BAD_REQUEST_PAYMENT_FAILED",
                    "gateway_error_description": "BAD_REQUEST_PAYMENT_FAILED"
                  }
                }';
        $response->body = $body;
        $this->app['card.payments']->method('sendRawRequest')->willReturn($response);
    }

    protected function mockCpsVerifyRequest()
    {
        $cardService = $this->getMockBuilder(CardPaymentService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRawRequest'])
            ->getMock();
        $this->app->instance('card.payments', $cardService);
        $response = new Requests_Response();
        $response->status_code = 200;
        $response->headers = ['Content-Type' => 'application/json'];
        $body = '{
                  "payment": {
                    "reference2" : "test111",
                    "two_factor_auth" : "test111"
                  },
                  "data": {
                    "amount" : 50000,
                    "gateway_success" : true
                  },
                  "headless": {},
                  "ivr" :{}
                }';
        $response->body = $body;
        $this->app['card.payments']->method('sendRawRequest')->willReturn($response);

    }

    protected function mockCps($terminal, $responder)
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal, $responder)
            {
                switch($responder)
                {
                    case 'headless_mock':
                        return $this->mockCpsHeadlessFlow($method, $url, $input, $terminal);
                    case 'auth_across_terminal_mock':
                        return $this->mockCpsAuthorizeAcrossTerminals($method, $url, $input, $terminal);
                    case 'headless_fatal_mock';
                        return $this->mockCpsHeadlessAuthError($method, $url, $input);
                    case 'headless_incorrect_otp':
                        return $this->mockCpsHeadlessIncorrectOtp($method, $url, $input);
                    case 'ivr_fallback_mock':
                        return $this->mockCpsIvrFallback($method, $url, $input);
                    case 'pay_error':
                        return $this->mockCpsPayGatewayError($method, $url, $input);
                    case 'capture_error':
                        return $this->mockCpsCaptureGatewayError($method, $url, $input, $terminal);
                    case 'callback_split':
                        return $this->mockCpsCallbackSplit($method, $url, $input, $terminal);
                    case 'empty_auth_code':
                        return $this->mockCpsEmptyAuthCode($url, $input, $terminal);
                    case 'not_enrolled_auth_split':
                        return $this->mockCpsNotEnrolledSplit($url, $input, $terminal);
                }
            });

        $cardService->shouldReceive('sendRequest')
            ->with('GET', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $responder)
            {
                switch ($responder)
                {
                    case 'entity_fetch':
                        return $this->mockCpsEntityFetch($url);
                }
            });
    }

    public function testAuthorizeViaCpsVisaSafeClickPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['vsc_authorization']);
        $terminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $authentication = array(
            'cavv'                  => '3q2+78r+ur7erb7vyv66vv\/\/8=',
            'cavv_algorithm'        => '1',
            'eci'                   => '05',
            'xid'                   => 'ODUzNTYzOTcwODU5NzY3Qw==',
            'enrolled_status'       => 'Y',
            'authentication_status' => 'Y',
            'provider_data'         => [
                'product_transaction_id'        => '1_156049293_714_62_l73q001m_CHECK211_156049293_714_62_l73q00',
                'product_merchant_reference_id' => '4aa1c9ffd4fc7ded80f73f1d98b35e8e24085404b6e01401',
                'product_type'                  => 'VCIND',
                'auth_type'                     => '3ds'
            ]
        );

        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['application'] = 'visasafeclick';
        $paymentArray['authentication'] = $authentication;

        unset($paymentArray['card']['cvv']);

        $this->enableCpsConfig();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->assertSatisfied = false;

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal, $authentication)
            {
                $this->assertEquals('authorize', $url);
                $this->assertEquals('POST', $method);
                $input = $input['input'];
                $this->assertArrayHasKey('terminals', $input);

                $inputTerminal = $input['terminals'][0];
                $this->assertEquals($inputTerminal['id'], $terminal->getId());
                $this->assertEquals($inputTerminal['auth']['authentication_gateway'], 'visasafeclick');

                $authenticate = $input['authenticate'];
                $this->assertEquals($authentication['cavv'], $authenticate['cavv']);
                $this->assertEquals($authentication['cavv_algorithm'], $authenticate['cavv_algorithm']);
                $this->assertEquals($authentication['eci'], $authenticate['eci']);
                $this->assertEquals($authentication['xid'], $authenticate['xid']);
                $this->assertEquals($authentication['enrolled_status'], $authenticate['enrolled_status']);
                $this->assertEquals($authentication['authentication_status'], $authenticate['authentication_status']);
                $this->assertEquals($authentication['provider_data']['product_transaction_id'], $authenticate['product_transaction_id']);
                $this->assertEquals($authentication['provider_data']['product_merchant_reference_id'], $authenticate['product_merchant_reference_id']);
                $this->assertEquals($authentication['provider_data']['product_type'], $authenticate['product_type']);
                $this->assertEquals($authentication['provider_data']['auth_type'], $authenticate['auth_type']);

                $this->assertSatisfied = true;

                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test'
                        ],
                    ],
                    'payment' => [
                        'two_factor_auth' => 'Y',
                        'reference2' => 'test',
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'visasafeclick',
                        'reference17' => '{"product_enrollment_id": "831eyJlbmMiOiJBMjU2R0NNIiwiYWxnIjoiUlNBLU9BRVAifQ.WwA2xBjK-sqL-hHIeCZ1nLRghkr-tOTVxWToFU5rH3aWlAnxsoVmvaBfBpYRPYsDBGDuAU0aQiXuJkB2ClECD07BrEcJ2eJ4hpsrYT2uF3ac_MTlLWvx8tz978DTvYPnD70-hoAVMPr6aDVLnz68-0fdx1oY0Iqum1W9Mwvr_dg8wvd_0oPpy_stPpclLCgwVTdcotcnyOfUxiiOF9CpQEoTkPzENh7QyBbNhLGri_HhUryPJN1FFFtdbCxq-NSRgKOQq__kXxv6RiY8RCKEop0a6iy7LkK6mynvf63kK1000"}',
                    ],
                ];
            });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertNotNull($payment['acquirer_data']['product_enrollment_id']);
        $this->assertEquals('visasafeclick', $payment['authentication_gateway']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals('authorized', $payment['status']);
        $this->disbaleCpsConfig();
    }

    public function testAuthorizeViaCpsVisaSafeClickStepUpPayment()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['vsc_authorization']);
        $terminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']['cvv']);

        $this->enableCpsConfig();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->mockCps($terminal, 'callback_split');

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('authorized', $payment['status']);

        $this->disbaleCpsConfig();
    }

    public function testAuthorizeWithoutAuthCodeFailure()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->enableCpsConfig();
        $this->mockCps($terminal, 'empty_auth_code');

        $paymentArray = $this->getDefaultPaymentArray();
        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            Exception\LogicException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $this->disbaleCpsConfig();
        $this->razorxValue = "on";
    }

    public function testLateAuthorizeViaCps()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

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

        $this->mockCpsVerifyRequest();
        $this->authorizedFailedPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('test111', $payment['reference2']);
    }

     /**
     * @return array
     */
    protected function getAVSPaymentArray(): array
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '4012010000000007';

        $paymentArray['customer_id'] = 'cust_100000customer';

        $paymentArray['billing_address'] = $this->getDefaultBillingAddressArray(true);

        $paymentArray['save'] = 1;

        return $paymentArray;
    }
}
