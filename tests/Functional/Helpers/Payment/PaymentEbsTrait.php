<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Gateway\Ebs\ResponseConstants as Response;

trait PaymentEbsTrait
{
    protected function runPaymentCallbackFlowEbs($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                    $url, $method, $content);
        }
        else
        {
            assert (false, 'Mock is not enabled');
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    public function getErrorInRefund()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content = '<output errorCode="29" error="Insufficient balance"/>';
            })->mock();

        $this->setMockServer($server);
    }
    public function getErrorInVerify()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content = '<output errorCode="5"/>';
            })->mock();

        $this->setMockServer($server);
    }

    public function getTimeoutInVerify()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                throw new GatewayTimeoutException(
                    'cURL error 28: Operation timed out after ' .
                    '10001 milliseconds with 0 bytes received');
            })->mock();

        $this->setMockServer($server);
    }

    public function getErrorInCallback()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content[Response::RESPONSE_CODE] = '1';
            })->mock();

        $this->setMockServer($server);
    }
}
