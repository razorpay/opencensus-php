<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Gateway\FirstData\SoapWrapper;

trait PaymentFirstDataTrait
{
    protected function runPaymentCallbackFlowFirstData($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

    protected function getErrorInAuth()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content['approval_code'] = 'N:87:Bad Track Data';
                $content['status'] = 'DECLINED';
            })->mock();

        $this->setMockServer($server);
    }

    protected function getErrorInReturn()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content['ApprovalCode'] = 'N:-5008:Order does not exist.';
                $content['TransactionResult'] = 'FAILED';
            })->mock();

        $this->setMockServer($server);
    }

    protected function getErrorInCapture()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content['ApprovalCode'] = 'N:-10503:Invalid amount or currency';
                $content['TransactionResult'] = 'FAILED';
            })->mock();

        $this->setMockServer($server);
    }

    protected function getErrorInInquiry()
    {
        $server = $this->mockServer()
            ->shouldReceive('content')
            ->andReturnUsing(function (& $content)
            {
                $content = SoapWrapper::ERROR_ACTION_RESPONSE;
            })->mock();

        $this->setMockServer($server);
    }
}