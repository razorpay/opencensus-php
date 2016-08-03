<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

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
            ;
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    public function getErrorInRefund()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content = '<output  errorCode="29"  error="Insufficient balance"  />';
                return $content;
            })->mock();

        $this->setMockServer($server);
    }

    public function getErrorInVerify()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content = '<output  errorCode="5" />';

                return $content;
            })->mock();

        $this->setMockServer($server);
    }

    public function getHackedResponse()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content['IsFlagged'] = 'YES';
                return $content;
            })->mock();

        $this->setMockServer($server);
    }
}
