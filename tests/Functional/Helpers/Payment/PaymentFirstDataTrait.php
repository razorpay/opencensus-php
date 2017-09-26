<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Requests;
use RZP\Gateway\FirstData\SoapWrapper;
use RZP\Exception;

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
        $this->mockServerContentFunction(function (& $content)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] = 'N:87:Bad Track Data';
                $content['status']        = 'DECLINED';
            }
        });
    }

    protected function removeApprovalCodeInAuth()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] = null;
                $content['fail_rc']       = '5003';
                $content['fail_reason']   = 'The order already exists in the database.';
                $content['status']        = 'FAILED';
            }
        });
    }

    protected function removeApprovalCodeFailRc()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] = null;
            }
        });
    }

    protected function getUnknownErrorInAuth()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] = "N:666:Devil's Own Error";
                $content['status']        = 'DECLINED';
            }
        });
    }

    protected function getErrorInReturn()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['ApprovalCode']      = 'N:-5008:Order does not exist.';
            $content['TransactionResult'] = 'FAILED';
        });
    }

    protected function getErrorInCapture()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content['ApprovalCode']      = 'N:-10503:Invalid amount or currency';
            $content['TransactionResult'] = 'FAILED';
        });
    }

    protected function getOveriddenApprovalCode($code)
    {
        $this->mockServerContentFunction(function (& $content) use ($code)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] = $code;
            }
        });
    }

    protected function getErrorInInquiry()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content = SoapWrapper::ERROR_ACTION_RESPONSE;
        });
    }

    protected function getTimeoutInCapture()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\GatewayTimeoutException('operation timed out');
        });
    }

    protected function clearMockFunction()
    {
        $this->mockServerContentFunction(function(& $input)
        {
        });
    }

    protected function getErrorInVerifyRefund()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                // Simulating a random error from FirstData
                // Can't use ERROR_ACTION_RESPONSE, because VerifyRefund
                // interprets that as a refund failed, and retries.
                // We just want to throw an error somehow.
                $content = SoapWrapper::ERROR_SOAP_SKELETON;
            }
        });
    }

    protected function setInvalidAuthField($field)
    {
        $server = $this->mockServer()
                       ->shouldReceive('request')
                       ->andReturnUsing(
                        function (& $request) use ($field)
                        {
                            $request[$field] = 'invld_' . $field;
                        })
                       ->mock();

        $this->setMockServer($server);
    }
}
