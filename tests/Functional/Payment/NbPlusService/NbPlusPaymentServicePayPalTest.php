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
use RZP\Services\SplitzService;

class NbPlusPaymentServicePayPalTest extends TestCase
{
    use PaymentNbplusTrait;

    const WALLET = 'paypal';
    protected $merchantId = '10000000000000';
    const LOCAL_CUSTOMER = '100000customer';
    const GLOBAL_CUSTOMER = '10000gcustomer';

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
        'wallet',
    ];

    const CALLBACK_ACTION_INPUT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
    ];

    const VERIFY_ACTION_INPUT = [
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
    ];

    const GATEWAY_INPUT = [
        'token',
        'PayId',
        'status',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $this->gateway = 'wallet_paypal';

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

        $this->terminal = $this->fixtures->create('terminal:shared_paypal_terminal', ['currency' => ['USD'], 'merchant_id' => $this->merchantId]);

        $this->fixtures->merchant->enableWallet($this->merchantId, self::WALLET);

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Wallet', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->ba->privateAuth();
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
                        $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                        break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT][NbPlusPaymentService\Request::GATEWAY],self::GATEWAY_INPUT);
                    break;
            }
        });

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());

        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];

        $this->payment['dcc_currency'] = 'USD';

        $this->payment['currency_request_id'] = $currencyRequestId;

        $this->payment['contact'] = "8448720400";

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testVerifyPayment()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());

        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];

        $this->payment['dcc_currency'] = 'USD';

        $this->payment['currency_request_id'] = $currencyRequestId;

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
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());

        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];

        $this->payment['dcc_currency'] = 'USD';

        $this->payment['currency_request_id'] = $currencyRequestId;

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

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        return $this->runPaymentCallbackFlowForPaypalGateway($response, $callback, $gateway);
    }

    protected function runPaymentCallbackFlowForPaypalGateway($response, & $callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

        $response = $this->submitPaymentCallbackRequest($data);

        return $response;
    }


    protected function makeFirstGatewayPaymentMockRequest($url, $method = 'get', $content = array())
    {
        $request = array(
            'url' => $url,
            'method' => strtoupper($method),
            'content' => $content);

        $response = $this->makeRequestParent($request);

        $statusCode = (int) $response->getStatusCode();

        if ($statusCode === 302)
        {
            return $response->getTargetUrl();
        }
        else if ($statusCode === 200)
        {
            // Probably a form here.
            // Return url, method, content from that.

            return $this->getFormRequestFromResponse($response->getContent(), $url);
        }
    }

    private function getDefaultPaymentFlowsRequestData()
    {
        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'wallet' => 'paypal'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

}
