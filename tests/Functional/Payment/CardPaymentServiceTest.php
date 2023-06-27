<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use App;
use Mockery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use \WpOrg\Requests\Response;
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
use RZP\Models\Card\Network;
use RZP\Models\Payment\Entity;
use RZP\Services\RazorXClient;
use RZP\Services\TerminalsService;
use RZP\Services\CardPaymentService;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\RazorxTreatment;


class CardPaymentServiceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TerminalTrait;


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
                    if ($feature === 'store_empty_value_for_non_exempted_card_metadata')
                        return 'off';

                    if ($feature === 'disable_rzp_tokenised_payment')
                    {
                        return 'off';
                    }

                    return $this->razorxValue;
                }) );

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
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

    /*
     * Merchant Country => IN
     * IIN Country => IN
     */
    public function testPaymentAuthorizedWithIndiaIINAndMerchantIndia()
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

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], false);

        $this->assertEquals($payment["currency"], "INR");

        $this->assertEquals($payment["international"], false);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }

    /*
     * Merchant Country => IN
     * IIN Country => US
     */
    public function testPaymentAuthorizedWithUSIINAndMerchantIndia()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'disable_native_currency']);

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'max_international_payment_amount' => 1000000
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->iin->edit('401200', [
            'country' => 'US'
        ]);

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

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], true);

        $this->assertEquals($payment["currency"], "INR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }

    /*
     * Merchant Country => MY
     * IIN Country => MY
     */
    public function testPaymentAuthorizedWithMalaysiaIINAndMerchantMalaysia()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->enableCpsConfig();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->iin->edit('401200', [
            'country' => 'MY',
            'network' => 'Union Pay'
        ]);

        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'                     => 'MY',
            'convert_currency'                 => null
        ]);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], false);

        $this->assertEquals($payment["currency"], "MYR");

        $this->assertEquals($payment["international"], false);

        $this->assertEquals('captured', $payment['status']);
    }


    /*
     * Merchant Country => IN
     * IIN Country => Unknown
     */
    public function testPaymentAuthorizedWithUnknownIINAndMerchantIN()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'disable_native_currency']);

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'max_international_payment_amount' => 1000000
        ]);

        $this->fixtures->iin->edit('401200', [
            'country' => null
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

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

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], true);

        $this->assertEquals($payment["currency"], "INR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }

    /*
     * Merchant Country => IN
     * IIN Country => MY
     */
    public function testPaymentAuthorizedWithMYIINAndMerchantIndia()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'disable_native_currency']);

        $this->enableCpsConfig();

        $paymentArray = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'max_international_payment_amount' => 1000000
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->iin->edit('401200', [
            'country' => 'MY'
        ]);

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

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], true);

        $this->assertEquals($payment["currency"], "INR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }


    /*
     * Merchant Country => MY
     * IIN Country => IN
     *
     * Note : In this func, all the steps related to payment creation (validation, transformation, is international check etc.) are not
     * done. This payment creation request for Malaysian merchants is directed to pg router which has been mocked and we are creating  payment entity using mocked
     * data to perform the assertions.
     */
    public function testPaymentAuthorizedWithIndiaIINAndMerchantMalaysiaVisaNetwork()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->enableCpsConfig();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'                     => 'MY',
            'convert_currency'                 => null
        ]);

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

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

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card["international"], true);

        $this->assertEquals($payment["currency"], "INR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
    }


    /*
     * Merchant Country => MY
     * IIN Country => IN
     *
     * Note : In this func, all the steps related to payment creation (validation, transformation, is international check etc.) are not
     * done. This payment creation request for Malaysian merchants is directed to pg router which has been mocked and we are creating  payment entity using mocked
     * data to perform the assertions.
     */
    public function testPaymentAuthorizedWithIndiaIINAndMerchantMalaysia()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->enableCpsConfig();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'                     => 'MY',
            'convert_currency'                 => null
        ]);

        $this->fixtures->iin->edit('401200', [
            'network' => 'Union Pay'
        ]);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency'      => 'MYR',
            'amount'        => $payment['amount'],
            'international' => true
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals($payment["currency"], "MYR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals('captured', $payment['status']);
    }

    /*
     * Merchant Country => MY
     * IIN Country => Unknown
     *
     * Note : In this func, all the steps related to payment creation (validation, transformation, is international check etc.) are not
     * done. This payment creation request is directed to pg router which has been mocked and we are creating  payment entity using mocked
     * data to perform the assertions.
     */
    public function testPaymentAuthorizedWithUnknownIINAndMerchantMalaysia()
    {
        $terminal = $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->enableCpsConfig();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'                     => 'MY',
            'convert_currency'                 => null
        ]);

        $this->fixtures->iin->edit('401200', [
            'country' => null,
            'network' => 'Union Pay'
        ]);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency'      => 'MYR',
            'amount'        => $payment['amount'],
            'international' => true
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals($payment["currency"], "MYR");

        $this->assertEquals($payment["international"], true);

        $this->assertEquals('captured', $payment['status']);
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

    public function testAuthenticationRetryNotEnrolledFirstData()
    {
        $this->enableCpsConfig();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal1 = $this->fixtures->create('terminal:shared_first_data_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);
        $terminal2 = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $terminal=[$terminal1,$terminal2];

        $this->ba->privateAuth();
        $this->mockCardVault();

        $this->mockCps($terminal,"not_enrolled_retry_gateway");
        $payment = $this->getDefaultPaymentArray();

        $this->mockCanAuthorizeViaCPS();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->fixtures->merchant->addFeatures(['s2s','auth_split']);

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $this->makeRedirectToAuthorize($targetUrl);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals('hitachi',$payment['gateway']);

        $this->assertEquals(2, $payment['cps_route']);

    }

    public function testAuthenticationRetryNotEnrolledAxisMigs()
    {
        $this->enableCpsConfig();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal1 = $this->fixtures->create('terminal:shared_axis_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);
        $terminal2 = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $terminal=[$terminal1,$terminal2];

        $this->ba->privateAuth();
        $this->mockCardVault();

        $this->mockCps($terminal,"not_enrolled_retry_gateway");
        $payment = $this->getDefaultPaymentArray();

        $this->mockCanAuthorizeViaCPS();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->fixtures->merchant->addFeatures(['s2s','auth_split']);

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $this->makeRedirectToAuthorize($targetUrl);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authenticated', $payment['status']);
        $this->assertEquals('hitachi',$payment['gateway']);

        $this->assertEquals(2, $payment['cps_route']);

    }

    public function testAuthorizeCardPaymentForUnionPayViaPgRouter()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'     => 'MY',
            'convert_currency' => null
        ]);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->fixtures->iin->edit('401200', [
            "country" => "MY",
            "network" => "Union Pay"
        ]);

        $payment['currency'] = "MYR";

        $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment["amount"], 50000);

        $this->assertEquals($payment["status"], 'captured');
    }

    public function testAuthorizeViaMpgsCardTerminalVisa()
    {
        $terminal = $this->fixtures->create('terminal:shared_mpgs_terminal');

        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'     => 'MY',
            'convert_currency' => null
        ]);

        $payment = $this->getDefaultPaymentArray();

        $this->enableCpsConfig();

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->mockCps($terminal, "auth_across_terminal_mock");

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->edit('401200', [
            "country" => "MY",
            "network" => "Visa"
        ]);

        $payment['currency'] = "MYR";

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment["amount"], 50000);

        $this->assertEquals($payment['gateway'], 'mpgs');

        $this->assertEquals($payment['authentication_gateway'], 'mpi_blade');

        $this->assertEquals($payment["status"], 'captured');

        $this->assertEquals($payment['terminal_id'], $terminal->getId());
    }

    public function testAuthorizeViaMpgsCardTerminalVisaDisableExperiment()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'country_code'     => 'MY',
            'convert_currency' => null
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'disable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $this->fixtures->iin->edit('401200', [
            "country" => "MY",
            "network" => "Visa"
        ]);

        $payment['currency'] = "MYR";

        $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment["amount"], 50000);

        $this->assertEquals($payment["status"], 'captured');
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

    public function testAuthorizationWithHeadlessViaCpsForPaytmOptimiser()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:card_paytm_terminal', [
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
        $payment['force_terminal_id'] = 'term_100CPaytmTrmnl';

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id']);

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
        $this->assertEquals("paytm", $payment['gateway']);

        $this->disbaleCpsConfig();
        $this->razorxValue = "on";
    }

    public function testAuthorizationWithHeadlessViaCpsForOptimiserBilldeskOptimiser()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:card_billdesk_optimiser_terminal', [
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
        $payment['force_terminal_id'] = 'term_100BDOptiTrmnl';

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id']);

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
        $this->assertEquals("billdesk_optimizer", $payment['gateway']);

        $this->disbaleCpsConfig();
        $this->razorxValue = "on";
    }

    public function testAuthorizationWithHeadlessViaCpsForPayuOptimiser()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:card_payu_terminal', [
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
        $payment['force_terminal_id'] = 'term_1000CPayuTrmnl';

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id']);

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
        $this->assertEquals("payu", $payment['gateway']);

        $this->disbaleCpsConfig();
        $this->razorxValue = "on";
    }

    public function testAuthorizationWithHeadlessViaCpsForCashfreeOptimiser()
    {
        $this->razorxValue = "cardps";

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:card_cashfree_terminal', [
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
        $payment['force_terminal_id'] = 'term_100CashfreeTml';

        $this->fixtures->merchant->addFeatures(['allow_force_terminal_id']);

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
        $this->assertEquals("cashfree", $payment['gateway']);

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
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

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

    public function testAuthorizeWithFailedAVSBillingAddressParam()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'merchants_refund_create_v1.1' or $feature === 'store_empty_value_for_non_exempted_card_metadata' or $feature === 'non_merchant_refund_create_v1.1')
                    {
                        return 'off';
                    }
                    return 'on';
                }));

        $this->enableCpsConfig();

        $this->fixtures->merchant->addFeatures(['avs']);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

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
                        'avs_result' => 'A'
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        });

        $payment = $this->getDbLastPayment();

        $this->assertEquals("refunded",$payment->getStatus());

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS, $payment->getInternalErrorCode());

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

    public function testCreatePaymentWithTokenPanAndCryptogram()
    {
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
                                if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                                {
                                    return 'off';
                                }

                                return 'on';

                            }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
                'type' => [
                    'non_recurring' => '1',
            ]
        ]);


        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('566', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);

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
                        $this->assertEquals('566', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);


        $paymentArray['card']['number'] = '4044649165235890';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('999999', $card['iin']);
        $this->assertEquals('404464916', $card['token_iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreateWithTokenPanAndCryptogramWithoutCvv()
    {
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
                    if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);

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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);


        $paymentArray['card']['number'] = '4044649165235890';
        // unset cvv for cvv optional
        unset($paymentArray['card']['cvv']);

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('999999', $card['iin']);
        $this->assertEquals('404464916', $card['token_iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreateWithTokenPanAndCryptogramWithEmptyCvv()
    {
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
                    if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);


        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);

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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);


        $paymentArray['card']['number'] = '4044649165235890';
        // unset cvv for cvv optional
        $paymentArray['card']['cvv'] = "";

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('999999', $card['iin']);
        $this->assertEquals('404464916', $card['token_iin']);

        $this->disbaleCpsConfig();
    }


    public function testCreatePaymentWithSavedNetworkTokenGlobal()
    {
        $this->mockSession();

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
                                if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata' )
                                {
                                    return 'off';
                                }

                                if ($feature === 'disable_rzp_tokenised_payment')
                                {
                                    return 'off';
                                }

                                return 'on';

                            }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
                'type' => [
                    'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '100000Razorpay',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

       $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '10000gcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '100000Razorpay',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);

        $paymentArray['token'] = 'token_100022custcard';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);

        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }


    public function testCreatePaymentWithSavedNetworkTokenLocal()
    {
        $this->mockSession();

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
                                if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata' )
                                {
                                    return 'off';
                                }

                                return 'on';

                            }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
                'type' => [
                    'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('566', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

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
                        $this->assertEquals('566', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

       $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '100000customer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);

        $paymentArray['token'] = 'token_100022custcard';
        $paymentArray['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreatePaymentWithSavedNetworkTokenLocalWithoutCvv()
    {
        $this->mockSession();

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
                    if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata' )
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '100000customer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);
        // unset cvv for cvv optional
        unset($paymentArray['card']['cvv']);

        $paymentArray['token'] = 'token_100022custcard';
        $paymentArray['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreatePaymentWithSavedNetworkTokenLocalWithEmptyCvv()
    {
        $this->mockSession();

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
                    if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata' )
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '100000customer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);
        // unset cvv for cvv optional
        $paymentArray['card']['cvv'] = "";

        $paymentArray['token'] = 'token_100022custcard';
        $paymentArray['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreatePaymentWithSavedNetworkTokenLocalWithoutCard()
    {
        $this->mockSession();

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
                    if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata' )
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);

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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
                        $this->assertEquals('123', $input['card']['cvv']);
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '100000customer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );

        // unset card for cvv optional
        unset($paymentArray['card']);

        $paymentArray['token'] = 'token_100022custcard';
        $paymentArray['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testIsPaymentProcessedWithTokenisedCardOnLocalMerchantWhenExpReturnsTrue()
    {
        $this->mockCardVaultWithCryptogram();

        $this->createPaymentAndRun('10000000000000', '100000customer', 'HDFC', 'Visa');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('HDFC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);
    }

    public function testIsPaymentProcessedWithActualCardOnLocalMerchantWhenExpReturnsFalse()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockCardVaultWithCryptogram(null, true);

        $this->createPaymentAndRun('100000Razorpay', '10000gcustomer', 'HDFC', 'Visa', 'off', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('HDFC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertNull($card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testIsPaymentProcessedWithTokenisedCardOnGlobalMerchantWhenExpReturnsTrue()
    {
        $this->mockCardVaultWithCryptogram();

        $this->createPaymentAndRun('100000Razorpay', '10000gcustomer', 'HDFC', 'Visa');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('HDFC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);
    }

    public function testIsPaymentProcessedWithActualCardOnGlobalMerchantWhenExpReturnsFalse()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->mockCardVaultWithCryptogram(null, true);

        $this->createPaymentAndRun('100000Razorpay', '10000gcustomer', 'HDFC', 'Visa', 'off', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(2, $payment['cps_route']);
        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('', $card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('HDFC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertNull($card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    protected function fixturesToSupportPayment($issuer, $network, $merchantId, $customerId)
    {
        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  $merchantId,
                'name'              =>  'test',
                'iin'               =>  '401200',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  $issuer,
                'network'           =>  $network,
                'last4'             =>  '1111',
                'type'              =>  'credit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => $customerId,
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  $merchantId,
            ]
        );
    }

    protected function createPaymentAndRun($merchantId, $customerId, $issuer, $network, $razorxValue = 'on', $useActualCard = false)
    {
        $this->mockSession();

        $this->mockRazorXTreatment($razorxValue);

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $this->mockCardVaultService($terminal, $useActualCard);

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixturesToSupportPayment($issuer, $network, $merchantId, $customerId);

        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);

        $paymentArray['token'] = 'token_100022custcard';

        if ($merchantId !== '100000Razorpay')
        {
            $paymentArray['customer_id'] = 'cust_100000customer';
        }

        $this->doAuthPayment($paymentArray);

        $this->disbaleCpsConfig();
    }

    protected function mockRazorXTreatment($value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($mid, $feature, $mode) use ($value)
            {
                if ($feature === 'card_payments_authorize_all_terminals' or  $feature === 'store_empty_value_for_non_exempted_card_metadata') {
                    return 'off';
                }

                if ($feature === RazorxTreatment::DISABLE_RZP_TOKENISED_PAYMENT)
                {
                    return 'off' ;
                }

                return 'on';
            }
        ));
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->atLeast()
            ->once()
            ->andReturn($output);
    }

    protected function mockCardVaultService($terminal, $useActualCard = false)
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal, $useActualCard)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        if ($useActualCard === false)
                        {
                            $this->assertEquals('test', $input['card']['cryptogram_value']);
                            $this->assertTrue($input['card']['tokenised']);
                            $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        }
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
                        if ($useActualCard === false)
                        {
                            $this->assertEquals('test', $input['card']['cryptogram_value']);
                            $this->assertTrue($input['card']['tokenised']);
                            $this->assertEquals('Razorpay', $input['card']['token_provider']);
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

                    case 'action/capture':
                        return [
                            'data' => [
                                'status' => 'captured',
                            ],
                        ];

                    case 'action/pay':
                        if ($useActualCard === false)
                        {
                            $this->assertEquals('test', $input['card']['cryptogram_value']);
                            $this->assertTrue($input['card']['tokenised']);
                            $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });
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

    private function mockCpsNotEnrolledRetryGateway($url,$input,$terminal)
    {
        switch($url){
            case 'action/authorize':
                if (in_array($input['gateway'],Payment\Gateway::$safeRetryGateways) === true){
                    return [
                        'data' => null,
                        'payment' => [
                        ],
                        'error' => [
                            'internal_error_code'       =>"GATEWAY_ERROR_VALIDATION_ERROR",
                            'gateway_error_code'        =>"400",
                            'gateway_error_description' =>"GATEWAY_ERROR_VALIDATION_ERROR",
                            'description'               =>"GATEWAY_ERROR_VALIDATION_ERROR",
                        ],
                        'headless' => [
                            'disable_iin'   => true,
                        ]
                    ];
                } else{
                    return [
                        'data' => [
                            'status' => 'authenticated',
                        ],
                    ];
                }
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
        $response = new \WpOrg\Requests\Response();
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
        $response = new \WpOrg\Requests\Response();
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
        $response = new \WpOrg\Requests\Response();
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
                    case 'not_enrolled_retry_gateway':
                        return $this->mockCpsNotEnrolledRetryGateway($url,$input,$terminal);
                    case 'redirect_mock':
                        return $this->mockCpsRedirectWorkflow($method,$url,$input,$terminal);
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

    protected function mockCanAuthorizeViaCPS(){
        $processor = \Mockery::mock(Payment\Processor\Processor::class)->makePartial();
        $this->app->instance('processor.cps',$processor);
        $processor->shouldReceive('canAuthorizeViaCps')->withAnyArgs()->andReturn(false);
    }

    protected function mockCardVaultWithCryptogram($callable = null, $useActualCard = false)
    {
        $app = App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = $callable ?: function ($route, $method, $input) use ($useActualCard)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    if($useActualCard == true)
                    {
                        $response['value'] = '4012001038443335';
                        break;
                    }
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'delete':
                    break;

                case 'tokens/cryptogram':
                        $response['service_provider_tokens'] = [
                            [
                                'type'  => 'network',
                                'name'  => 'Visa',
                                'provider_data'  => [
                                    'token_number' => '4044649165235890',
                                    'cryptogram_value' => 'test',
                                    'token_expiry_month' => 12,
                                    'token_expiry_year' => 2026,
                                ],
                            ]
                        ];
                    break;

                case 'tokens/fetch':
                    $response['service_provider_tokens'] = [
                        [
                            'type'  => 'network',
                            'name'  => 'Visa',
                            'provider_data'  => [
                                'token_number' => '4044649165235890',
                                'cryptogram_value' => 'test',
                                'token_expiry_month' => 12,
                                'token_expiry_year' => 2023,
                                'payment_account_reference' => '50014EES0F4P295H',
                                'token_reference_number' => 'DM4MMC00001564272',
                            ],
                            'tokenised_terminal_id' => 'term1',
                        ]
                    ];
                break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', null)
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    public function testSendGatewayErrorData()
    {
        $this->razorxValue = "cardps";
        $terminal = $this->fixtures->create('terminal:zaakpay_terminal', [
            'type' => [
                'non_recurring' => '1',
                'direct_settlement_with_refund' => '1',
            ]
        ]);

        $this->fixtures->edit('terminal', $terminal['id'], ['procurer' => 'merchant']);

        $this->fixtures->merchant->addFeatures(['expose_gateway_errors']);

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
                        'authentication_gateway' => 'axis_migs',
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"U123",
                        'gateway_error_description' =>"invalid_cvv",
                        'description'               =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                    ],
                ];
            });

        try
        {
            $this->doAuthPayment($paymentArray);
        }
        catch (Exception\BaseException $e)
        {
            $err = $e->getError()->toPublicArray();
            $this->assertArrayHasKey('gateway_data', $err['error']);
            $this->assertArrayHasKey('error_code', $err['error']['gateway_data']);
            $this->assertEquals('U123', $err['error']['gateway_data']['error_code']);
        }

        $paymentDbEntry = $this->getDbLastPayment();

        $payment = $this->fetchPayment($paymentDbEntry['public_id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertArrayHasKey('gateway_data', $payment);
        $this->assertEquals('U123', $payment['gateway_data']['error_code']);

        $this->razorxValue = "on";
    }

    // Optimizer gateways require additional network token details, like
    // PAR, TRN, TRID for payment processing.
    // These details are fetched within the authorize call to CPS
    // Request and responses are accordingly asserted
    public function testCreatePaymentWithSavedNetworkTokenGlobalForOptimiser()
    {

        $this->mockSession();
        $this->app = App::getFacadeRoot();

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
                    if ($feature === 'card_payments_authorize_all_terminals' or  $feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }

                    if ($feature === 'disable_rzp_tokenised_payment')
                    {
                        return 'off';
                    }

                    return 'on';

                }) );

        $this->enableCpsConfig();

        // set optimizer terminal
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:cashfree_terminal', [
            'type' => [
                'non_recurring' => '1',
                'direct_settlement_with_refund' => '1',
            ]
        ]);

        // mock response to terminal service to get tokenization terminal's TRID
        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getTokenisedTerminalResponseForTrid();
        },2);

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $this->mockCardVaultWithCryptogram();

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        $this->assertEquals('50014EES0F4P295H',$input['card']['payment_account_reference']);
                        $this->assertEquals('DM4MMC00001564272',$input['card']['token_reference_number']);
                        $this->assertEquals('visa_trid',$input['card']['token_reference_id']);

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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'ref2_number'
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
            });


        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '100000Razorpay',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '10000gcustomer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '100000Razorpay',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);
        unset($paymentArray['card']['payment_account_reference']);
        unset($paymentArray['card']['token_reference_number']);
        unset($paymentArray['card']['token_reference_id']);

        $paymentArray['token'] = 'token_100022custcard';

        // for optimizer terminals, payments should be auto captured
        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);
        $this->assertEquals('cashfree', $payment['gateway']);
        $this->assertEquals('cashfree', $payment['settled_by']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("ref2_number", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);

        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    public function testCreatePaymentWithSavedNetworkTokenLocalOptimizer()
    {
        $this->mockSession();

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
                                if ($feature === 'card_payments_authorize_all_terminals' or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                                {
                                    return 'off';
                                }

                                return 'on';

                            }) );

        $this->enableCpsConfig();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:cashfree_terminal', [
            'type' => [
                'non_recurring' => '1',
                'direct_settlement_with_refund' => '1',
            ]
        ]);

        // mock response to terminal service to get tokenization terminal's TRID
        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getTokenisedTerminalResponseForTrid();
        },2);

        $this->mockCardVaultWithCryptogram();

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function(string $method, string $url, array $input) use ($terminal)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
                        $this->assertEquals('50014EES0F4P295H',$input['card']['payment_account_reference']);
                        $this->assertEquals('DM4MMC00001564272',$input['card']['token_reference_number']);
                        $this->assertEquals('visa_trid',$input['card']['token_reference_id']);

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
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('Razorpay', $input['card']['token_provider']);
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
            });

        $paymentArray = $this->getDefaultTokenPanPaymentArray();

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'issuer'  => 'MDB',
            'network' => 'Visa',
            'type'    => 'debit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'merchant_id'       =>  '10000000000000',
                'name'              =>  'test',
                'iin'               =>  '411140',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'HDFC',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'debit',
                'vault'             =>  'visa',
                'vault_token'       =>  'test_token',
            ]
        );

       $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardToken',
                'customer_id'   => '100000customer',
                'method'        => 'card',
                'card_id'       => '100000003lcard',
                'used_at'       =>  10,
                'merchant_id'   =>  '10000000000000',
            ]
        );


        unset($paymentArray['card']['number']);
        unset($paymentArray['card']['cryptogram_value']);
        unset($paymentArray['card']['tokenised']);
        unset($paymentArray['card']['token_provider']);

        $paymentArray['token'] = 'token_100022custcard';
        $paymentArray['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);
        $this->assertEquals('cashfree', $payment['gateway']);
        $this->assertEquals('cashfree', $payment['settled_by']);

        $this->assertEquals(2, $payment['cps_route']);

        $this->assertEquals("3ds", $payment['auth_type']);
        $this->assertEquals("test", $payment['reference2']);
        $this->assertEquals("Y", $payment['two_factor_auth']);

        $card = $this->getLastEntity('card', true);

        $this->assertNotNull($card['trivia']);
        $this->assertEquals('IN', $card['country']);
        $this->assertEquals('ICIC', $card['issuer']);
        $this->assertEquals('credit', $card['type']);
        $this->assertEquals('404464916', $card['token_iin']);
        $this->assertEquals('999999', $card['iin']);

        $this->disbaleCpsConfig();
    }

    protected function mockCpsRedirectWorkflow(string $method, string $url, array $input, $terminal)
    {
        switch ($url)
        {
            case 'action/authorize':

                $payment = $this->getDbLastPayment();

                return [
                    'data' => [
                        'content' => [],
                        'method' => 'GET',
                        // 'url' => $this->getPaymentRedirectTo3dsUrl($payment->getPublicId()),
                        'url' => 'https://api.razorpay.com/v1/gateway/acs/mpi_blade'
                        ,
                    ],
                    'payment' => [
                        'auth_type' => "3ds",
                    ],
                ];

            case 'action/callback':
                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'ref2_number'
                        ],
                    ],
                    'payment' => [
                        'reference2' => 'ref2_number',
                        'two_factor_auth' => "unknown",
                    ],
                ];

            default:
                return null;
        }
    }

    public function testFlipkartSendPaymentIdForError()
    {
        $this->razorxValue = "cardps";
        $terminal = $this->fixtures->create('terminal:zaakpay_terminal', [
            'type' => [
                'non_recurring' => '1',
                'direct_settlement_with_refund' => '1',
            ]
        ]);

        $this->fixtures->edit('terminal', $terminal['id'], ['procurer' => 'merchant']);

        $this->fixtures->merchant->addFeatures(['fk_new_error_response']);

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
                        'authentication_gateway' => 'axis_migs',
                    ],
                    'error' => [
                        'internal_error_code'       =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                        'gateway_error_code'        =>"U123",
                        'gateway_error_description' =>"invalid_cvv",
                        'description'               =>"GATEWAY_ERROR_UNKNOWN_ERROR",
                    ],
                ];
            });

        try
        {
            $this->doAuthPayment($paymentArray);
        }
        catch (Exception\BaseException $e)
        {
            $err = $e->getError()->toPublicArray();
            $this->assertArrayHasKey('payment_id', $err);
            $this->assertEquals($err['error']['metadata']['payment_id'], $err['payment_id']);
        }

        $this->razorxValue = "on";
    }

    public function testAuthorizeWith3dsCardsAndAVSBillingAddressParam()
    {
        $this->enableCpsConfig();

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::AVS]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::ADDRESS_REQUIRED]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $paymentArray = $this->getAVSPaymentArray();
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

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
                        'avs_result' => 'B',
                        'is_3DS_valid' => true,
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->ba->privateAuth();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWithNon3dsCardsAndCorrectAVSBillingAddressParam()
    {
        $this->enableCpsConfig();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::AVS]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::ADDRESS_REQUIRED]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $paymentArray = $this->getAVSPaymentArray();
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;
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
                        'avs_result' => 'B',
                        'is_3DS_valid' => false,
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->ba->privateAuth();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWithNon3DsCardAndFailedAVSBillingAddressParam()
    {

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'merchants_refund_create_v1.1' or $feature === 'store_empty_value_for_non_exempted_card_metadata' or $feature === 'non_merchant_refund_create_v1.1')
                    {
                        return 'off';
                    }
                    return 'on';
                }));

        $this->enableCpsConfig();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::AVS]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::ADDRESS_REQUIRED]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getAVSPaymentArray();
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;
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
                        'avs_result' => 'A',
                        'is_3DS_valid' => false,
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->ba->privateAuth();
            $this->doAuthPayment($paymentArray);
        });

        $payment = $this->getDbLastPayment();

        $this->assertEquals("refunded",$payment->getStatus());

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS, $payment->getInternalErrorCode());

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWith3dsCardsAndMerchantWithMandatoryAvsCheckAndValidAVSBillingAddressParam()
    {
        $this->enableCpsConfig();

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::AVS]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::ADDRESS_REQUIRED]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::MANDATORY_AVS_CHECK]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $paymentArray = $this->getAVSPaymentArray();
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

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
                        'avs_result' => 'B',
                        'is_3DS_valid' => true,
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $this->ba->privateAuth();
        $this->doAuthPayment($paymentArray);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testAuthorizeWith3DsCardAndMerchantWithMandatoryAVSCheckAndFailedAVSBillingAddressParam()
    {

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'merchants_refund_create_v1.1' or $feature === 'store_empty_value_for_non_exempted_card_metadata' or $feature === 'non_merchant_refund_create_v1.1')
                    {
                        return 'off';
                    }
                    return 'on';
                }));

        $this->enableCpsConfig();

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::AVS]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::ADDRESS_REQUIRED]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::MANDATORY_AVS_CHECK]);
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $terminal = $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1',
            ]
        ]);

        $paymentArray = $this->getAVSPaymentArray();
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;
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
                        'avs_result' => 'A',
                        'is_3DS_valid' => false,
                    ],
                    'payment' => [
                        'terminal_id' => $terminal->getId(),
                        'auth_type' => null,
                        'authentication_gateway' => 'mpi_blade'
                    ],
                ];
            });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($paymentArray)
        {
            $this->ba->privateAuth();
            $this->doAuthPayment($paymentArray);
        });

        $payment = $this->getDbLastPayment();

        $this->assertEquals("refunded",$payment->getStatus());

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS, $payment->getInternalErrorCode());

        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment->getCpsRoute());

        $this->assertEquals('mpi_blade', $payment['authentication_gateway']);

        $this->assertTrue($this->assertSatisfied);
    }

    public function testForceAuthorizeFailedCardPayment()
    {
        $this->markTestSkipped();
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

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('fetchAuthorizationData')
            ->andReturnUsing(function( array $input)
            {
                $responseItems = [];
                $response = $this->mockCpsAuthFetchResponse($input['payment_ids']);
                foreach ($response['items'] as $item)
                {
                    $responseItems[$item['payment_id']] = $item;
                }
                return $responseItems;
            });


        $content = $this->getDefaultCardForceAuthorizePayload();
        $content['payment']['id'] = substr($payment['id'] ,4);
        $content['meta']['force_auth_payment'] = true;

        $response = $this->markForceAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
        $this->assertEquals(true,$response['success']);
        $this->assertNotNull($updatedPayment['transaction_id'],'Transaction Id should not be null');
        $this->assertEquals(0,$response['gateway_fee']);
        $this->assertEquals(0,$response['gateway_service_tax']);
    }

    public function testAuthorizeFailedCardPaymentViaVerifyDuringRecon()
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
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);

        $content = $this->getDefaultCardForceAuthorizePayload();
        $content['payment']['id'] = substr($payment['id'],4);
        $content['meta']['force_auth_payment'] = false;
        $this->mockCpsVerifyRequest();
        $response = $this->markForceAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertEquals(true,$response['success']);
        $this->assertNotNull($updatedPayment['transaction_id'],'Transaction Id should not be null');
        $this->assertEquals(0,$response['gateway_fee']);
        $this->assertEquals(0,$response['gateway_service_tax']);
    }

    public function testAuthorizeFailedCardPaymentForAlreadyAuthorisedPayment()
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

        $this->mockCps($terminal, 'headless_mock');

        $this->doAuthPayment($paymentArray);


        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(Payment\Entity::CARD_PAYMENT_SERVICE, $payment['cps_route']);
        $this->assertEquals('authorized', $payment['status']);

        $content = $this->getDefaultCardForceAuthorizePayload();
        $content['payment']['id'] = $payment['id'];
        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/card/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);

     $this->assertNull($payment['transation_id']);
    }

    public function testUpdatePostReconData()
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

        $this->assertEquals('captured', $payment['status']);

        $content = $this->getDefaultCardPostReconArray();

        $content['payment_id'] = substr($payment['id'],4);

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $content['card']['rrn'] = "3234566788";

        $content['card']['arn'] = "AXIS003421Y3T";

        $content['card']['auth_code'] = "435676";

        $response = $this->makeUpdatePostReconRequestAndGetContentForCard($content);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals('3234566788', $paymentEntity['reference16']);

        $this->assertEquals('435676', $paymentEntity['reference2']);

        $this->assertEquals('AXIS003421Y3T', $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotEmpty($transactionEntity['reconciled_at']);

        $this->assertEquals('mis', $transactionEntity['reconciled_type']);

        $this->assertEquals('18', $transactionEntity['gateway_service_tax']);

        $this->assertEquals('118', $transactionEntity['gateway_fee']);

        $this->assertTrue($response['success']);
    }


    public function testUpdatePostReconDataForAlreadyReconciled()
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

        $this->assertEquals('captured', $payment['status']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transactionEntity['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultCardPostReconArray();

        $content['payment_id'] = substr($payment['id'],4);

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $content['card']['rrn'] = "3234566788";

        $content['card']['arn'] = "AXIS003421Y3T";

        $content['card']['auth_code'] = "435676";

        $response = $this->makeUpdatePostReconRequestAndGetContentForCard($content);

        $this->assertFalse($response['success']);

        $this->assertEquals('ALREADY_RECONCILED', $response['error']['code']);
    }

    public function testCreateTransactionDuringReconProcess()
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

        $content = $this->getDefaultArtPayloadForCreatingTransaction();

        $content['payment_id'] = substr($payment['id'],4);

        $response = $this->makeCreateTransactionRequestAndGetContent($content);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertEquals($transactionEntity['gateway_fee'],0);

        $this->assertEquals($transactionEntity['gateway_service_tax'],0);

        $this->assertEquals($response['gateway_service_tax'],0);

        $this->assertEquals($response['gateway_fee'],0);

        $this->assertTrue($response['success']);
    }

    public function testCreateTransactionFailureDuringReconProcess()
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

        $content = $this->getDefaultArtPayloadForCreatingTransaction();

        $content['payment_id'] = substr($payment['id'],5);


        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/recon/create/transaction',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }


     public function testCreateTransactionDuringReconProcessForAlreadyCreatedTransaction()
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

        $transactionEntity1 = $this->getDbLastEntity('transaction');

        $content = $this->getDefaultArtPayloadForCreatingTransaction();

        $content['payment_id'] = substr($payment['id'],4);

        $response = $this->makeCreateTransactionRequestAndGetContent($content);

        $transactionEntity2 = $this->getDbLastEntity('transaction');

        $this->assertEquals($transactionEntity2['gateway_fee'],0);

        $this->assertEquals($transactionEntity2['gateway_service_tax'],0);

        $this->assertEquals($response['gateway_service_tax'],0);

        $this->assertEquals($response['gateway_fee'],0);

        $this->assertTrue($response['success']);

        $this->assertEquals($transactionEntity1['id'],$transactionEntity2['id']);

    }

    protected function mockCpsAuthFetchResponse($input)
    {
        $res = array_map(function($paymentId)
        {
            return [
                'id'         => 'acasd123',
                'payment_id' => $paymentId,
                'auth_code'  => 'A1233V',
                'rrn'        => '123456789101',
            ];
        }, $input);

        return [
            'count'  => sizeof($input),
            'entity' => 'authorize',
            'items'  => $res,
        ];
    }

}
