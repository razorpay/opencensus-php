<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mail;
use Mockery;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NbPlusPaymentServiceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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
        'gateway_config',
        'method_data',
    ];

    const CALLBACK_ACTION_INPUT = [
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway_config',
        'method_data'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
                  ->method('getTreatment')
                  ->will($this->returnCallback(
                      function ($mid, $feature, $mode)
                      {
                            return 'nbplusps';
                      })
                  );

        $this->terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $this->enableNbPlusConfig();

        // This may be made generic when there are more than just netbanking methods migrated to new service
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testAuthorize()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $this->assertEquals(Payment\Gateway::ATOM, $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($content[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($content[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
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
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

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

        $this->verifyPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
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

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);

        $this->assertEquals('GATEWAY_ERROR', $payment[Payment\Entity::ERROR_CODE]);

        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $content);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }

    protected function mockCallbackFromGateway($url, $method = 'get', $content = array())
    {
        $request = array(
            'url' => $url,
            'method' => strtoupper($method),
            'content' => $content);

        $response = $this->makeRequestParent($request);

        return $response;
    }

    // TODO
    /*public function testAuthorizeViaCpsCheckoutAuthorizePayment()
    {
    }

    public function testCaptureViaNbPlusService()
    {
    }

    public function testAuthorizeViaCpsS2SAuthorize()
    {
    }*/

    protected function mockServerContentFunction($closure)
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing($closure);
    }

    protected function mockServerRequestFunction($closure)
    {
        $this->nbPlusService->shouldReceive('request')->andReturnUsing($closure);
    }
}
