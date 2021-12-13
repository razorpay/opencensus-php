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

class NbPlusPaymentServiceAppsTest extends TestCase
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
    ];

    const CALLBACK_ACTION_INPUT = [
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
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
                    return 'nbplusps';
                })
            );

        $this->terminal = $this->fixtures->create('terminal:twid_terminal');

        $this->provider = "twid";

        $this->fixtures->merchant->enableApp('10000000000000', 'twid');

        $this->enableNbPlusConfig();

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\AppMethod', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultAppPayment($this->provider);

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
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $acquirerData = [
            'transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment[Payment\Entity::ACQUIRER_DATA]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithINRCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


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

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithUSDCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'USD';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'USD';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


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

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithGatewaySupportedCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


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

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);

        $this->assertNull($paymentMeta);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithGatewaySupportedCurrencyButChooseDCC()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'GBP';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


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

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testVerify()
    {
        $paymentArray = $this->getDefaultAppPayment($this->provider);

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

        $paymentArray = $this->getDefaultAppPayment($this->provider);

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

        $paymentArray = $this->getDefaultAppPayment($this->provider);

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
            'transaction_id' => '1234'
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

        $paymentArray = $this->getDefaultAppPayment($this->provider);

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

        $paymentArray = $this->getDefaultAppPayment($this->provider);

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

        $paymentArray = $this->getDefaultAppPayment($this->provider);

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

    private function getDefaultPaymentFlowsRequestData()
    {
        $flowsData = [
            'content' => ['amount' => 100000, 'currency' => 'INR', 'provider' => 'trustly'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

    private function setConfigurationInternationalApp($provider = 'trustly'){

        $this->terminal = $this->fixtures->create('terminal:emerchantpay_terminal');

        $this->provider = $provider;

        $this->fixtures->merchant->enableApp('10000000000000', $provider);

        $this->fixtures->merchant->edit('10000000000000');

        $this->payment = $this->getDefaultAppPayment($this->provider);

        $this->ba->privateAuth();
    }
}
