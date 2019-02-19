<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayTimeoutException;
use RZP\Exception\PaymentVerificationException;
use RZP\Gateway\Ebs\ResponseConstants as Response;

trait PaymentEbsTrait
{
    protected function runPaymentCallbackFlowEbs($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->ba->directAuth();

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                    $url, $method, $content);
        }
        else
        {
            assertTrue (false, 'Mock is not enabled');
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
