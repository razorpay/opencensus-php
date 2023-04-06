<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mockery;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbPlusPaymentServiceNetbankingTest extends TestCase
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
        'merchant_detail',
        'method_data',
    ];

    const CALLBACK_ACTION_INPUT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'method_data'
    ];

    const PREPROCESS_CALLBACK_INPUT = [
        'gateway_data'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

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
                          if ($feature == 'netbanking_enable_webhooks_atom')
                          {
                              return 'enablewebhooks';
                          }
                          if (($feature === 'fetch_refunds_data_from_scrooge') and
                              ($mid === 'netbanking_ausf'))
                          {
                              return 'on';
                          }
                          return 'nbplusps';
                      })
                  );

        $this->terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $this->bank = "IDIB";

        // This may be made generic when there are more than just netbanking methods migrated to new service
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testAuthorize()
    {
        $paymentArray = $this->payment;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    if($assertContent['input']['payment']['gateway'] === 'netbanking_canara')
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], array_merge(self::AUTHORIZE_ACTION_INPUT, ['payment_fee']));
                    }
                    else
                    {
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    }
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::PREPROCESS_CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::PREPROCESS_CALLBACK_INPUT);
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $acquirerData = [
            'bank_transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment[Payment\Entity::ACQUIRER_DATA]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testVerify()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $response = $this->doAuthPayment($paymentArray);

        $this->verifyPayment($response['razorpay_payment_id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentFailedVerifySuccess()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            },
            PaymentVerificationException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentFailedWebhookSuccess()
    {
        $this->markTestSkipped('webhooks are blocked for temp');
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $response = $this->mockWebhookFromBank();

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(true, $payment[Payment\Entity::LATE_AUTHORIZED]);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]);
    }

    protected function mockWebhookFromBank()
    {
        $gatewayPayment = $this->getLastEntity('payment', true);

        $content = [
            'paymentId' => substr($gatewayPayment['id'], 4),
            'VERIFIED' => 'SUCCESS',
        ];

        $request = [
            'content' => $content,
            'url' => '/gateway/netbanking/atom/s2scallback/test',
            'method' => 'post'
        ];

        // Fire s2s webhook
        return $this->makeRequestAndGetContent($request);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->authorizedFailedPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertTrue($payment[Payment\Entity::LATE_AUTHORIZED]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $acquirerData = [
            'bank_transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment[Payment\Entity::ACQUIRER_DATA]);
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]
                    ],
                ];
            }

            if ($action === NbPlusPaymentService\Action::VERIFY)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_CANCELLED_BY_USER'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);

        $this->verifyPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);

        // error code is updated on verify response
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testAuthorizeHandleErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE  => null,
                NbPlusPaymentService\Response::ERROR     => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                    ]
                ],
            ];
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $payment[Payment\Entity::ERROR_CODE]);

        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testAuthorizeHandleGatewayErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'GATEWAY_ERROR_UNKNOWN_ERROR'
                    ]
                ],
            ];
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);

        $this->assertEquals('GATEWAY_ERROR', $payment[Payment\Entity::ERROR_CODE]);

        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testNbPlusGatewayErrorStore()
    {
        $this->fixtures->merchant->addFeatures(['expose_gateway_errors']);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'GATEWAY_ERROR_UNKNOWN_ERROR',
                        'gateway_error_code'                            =>  'ABC',
                        'gateway_error_description'                     =>  'invalid_account',
                    ]
                ],
            ];
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->makeRequestAndCatchException(function() use ($paymentArray)
        {
            $this->doAuthPayment($paymentArray);
        }, GatewayErrorException::class);

        $paymentDbEntry = $this->getDbLastPayment();

        $payment = $this->fetchPayment($paymentDbEntry['public_id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertArrayHasKey('gateway_data', $payment);
        $this->assertEquals('ABC', $payment['gateway_data']['error_code']);
    }

    public function testEmptyCallback()
    {
        if($this->bank !== "ICIC")
        {
            $this->bank = 'ICIC';

            $this->terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

            $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
        }

        $paymentArray = $this->payment;

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content['response']['data']['next']['redirect']['content'] = [];
            }
        });

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

}
