<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mockery;

use RZP\Error\Error;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbplusPaymentServiceCardlessEmiTest extends TestCase
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

    protected function setUp(): void
    {
        $this->provider = 'walnut369';

        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $this->gateway = 'cardless_emi';

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

        $this->fixtures->merchant->enableCardlessEmi();

        $this->terminal = $this->fixtures->create('terminal:shared_cardless_emi_walnut369_terminal');

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\CardlessEmi', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        unset($this->payment['emi_duration']);

    }

    public function testAuthorize()
    {
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
            }
        });

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testVerify()
    {
        $response = $this->doAuthAndCapturePayment($this->payment);

        $this->verifyPayment($response['id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::CAPTURED, $payment[Payment\Entity::STATUS]);

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

        $this->doAuthAndCapturePayment($this->payment);

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

        $paymentArray = $this->payment;

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

        $paymentArray = $this->payment;

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

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]); // TODO:
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

        $paymentArray = $this->payment;

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

        $paymentArray = $this->payment;

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

}
