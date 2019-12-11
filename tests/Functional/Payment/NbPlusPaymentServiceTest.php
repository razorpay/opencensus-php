<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mail;
use Mockery;

use RZP\Constants\Mode;
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
                        }));

        $this->terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $this->enableNbPlusConfig();

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlusPaymentService', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testAuthorizeViaNbPlusPaymentUpdateService()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
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

    public function testAuthorizeViaNbPlusPaymentUpdateHandleErrorResponse()
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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('BAD_REQUEST_ERROR', $payment['error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment['internal_error_code']);
    }

    public function testAuthorizeViaNbPlusPaymentUpdateHandleErrorResponseServerError()
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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('SERVER_ERROR', $payment['error_code']);
        $this->assertEquals('SERVER_ERROR', $payment['internal_error_code']);
    }

    public function testAuthorizeViaNbPlusPaymentUpdateHandleErrorResponseGatewayError()
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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment['cps_route']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($this->terminal->getId(), $payment['terminal_id']);
        $this->assertEquals('GATEWAY_ERROR', $payment['error_code']);
        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment['internal_error_code']);
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
}
