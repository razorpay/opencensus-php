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
        $this->mockServerContentFunction(function (& $content)
        {
            $content = '<output errorCode="29" error="Insufficient balance"/>';
        });
    }

    public function getErrorInVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content = '<output errorCode="5"/>';
        });
    }

    public function getFatalErrorInVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
           throw new FatalThrowableError();
        });
    }

    public function getTimeoutInVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new GatewayTimeoutException(
                'cURL error 28: Operation timed out after ' .
                '10001 milliseconds with 0 bytes received');
        });
    }

    public function getErrorInCallback()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content[Response::RESPONSE_CODE] = '1';
        });
    }

    public function getHackedResponse()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['IsFlagged'] = 'YES';
        });
    }
}
