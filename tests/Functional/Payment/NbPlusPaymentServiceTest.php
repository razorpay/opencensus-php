<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mail;
use Mockery;

use RZP\Constants\Mode;
use RZP\Exception\BadRequestException;
use RZP\Exception\PaymentVerificationException;
use RZP\Models\Payment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
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
        'gateway',
    ];

    const CALLBACK_ACTION_INPUT = [
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'gateway_config',
    ];

    // TODO: amount mismatch test case for verify?
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

        $this->terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->enableNbPlusConfig();

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlusPaymentService', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testAuthorize()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $this->assertEquals('billdesk', $content['gateway']);

            switch ($action)
            {
                case 'authorize':
                    $this->assertEquals('authorize', $content['action']);
                    $this->assertArrayKeysExist($content['input'], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case 'callback':
                    $this->assertEquals('callback', $content['action']);
                    $this->assertArrayKeysExist($content['input'], self::CALLBACK_ACTION_INPUT);
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $acquirerData = [
            'bank_transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment['acquirer_data']);

        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
    }

    public function testVerify()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $response = $this->doAuthPayment($paymentArray);

        $this->verifyPayment($response['razorpay_payment_id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testPaymentFailedVerifySuccess()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = [
                    'data' => null,
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       => 'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_code'        => 'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_description' => 'BAD_REQUEST_PAYMENT_FAILED',
                        'description'               => 'BAD_REQUEST_PAYMENT_FAILED',
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
                $this->verifyPayment($payment['id']);
            },
            PaymentVerificationException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals(0, $payment['verified']);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = [
                    'data' => null,
                    'payment' => [
                    ],
                    'error' => [
                        'internal_error_code'       => 'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_code'        => 'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_description' => 'BAD_REQUEST_PAYMENT_FAILED',
                        'description'               => 'BAD_REQUEST_PAYMENT_FAILED',
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

        $this->assertEquals('failed', $payment['status']);

        $this->authorizedFailedPayment($payment['id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertTrue($payment['late_authorized']);

        $this->assertEquals('authorized', $payment['status']);

        $acquirerData = [
            'bank_transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment['acquirer_data']);
    }

    public function testAuthorizeHandleErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                'data' => null,
                'payment' => [
                ],
                'error' => [
                    'internal_error_code'       => 'BAD_REQUEST_PAYMENT_FAILED',
                    'gateway_error_code'        => 'BAD_REQUEST_PAYMENT_FAILED',
                    'gateway_error_description' => 'BAD_REQUEST_PAYMENT_FAILED',
                    'description'               => 'BAD_REQUEST_PAYMENT_FAILED',
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

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment['internal_error_code']);
    }

    public function testAuthorizeHandleServerErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                'data' => null,
                'payment' => [
                ],
                'error' => [
                    'internal_error_code'       => 'SERVER_ERROR',
                    'gateway_error_code'        => 'SERVER_ERROR',
                    'gateway_error_description' => 'SERVER_ERROR',
                    'description'               => 'SERVER_ERROR',
                ],
            ];
        });

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            \RZP\Exception\LogicException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('SERVER_ERROR', $payment['error_code']);
        $this->assertEquals('SERVER_ERROR', $payment['internal_error_code']);
    }

    public function testAuthorizeHandleGatewayErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                'data' => null,
                'payment' => [],
                'error' => [
                    'internal_error_code'       => 'GATEWAY_ERROR_UNKNOWN_ERROR',
                    'gateway_error_code'        => 'GATEWAY_ERROR_UNKNOWN_ERROR',
                    'gateway_error_description' => 'GATEWAY_ERROR_UNKNOWN_ERROR',
                    'description'               => 'GATEWAY_ERROR_UNKNOWN_ERROR',
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

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);
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
