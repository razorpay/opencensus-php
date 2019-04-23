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
            if ($this->isOtpCallbackUrl($url) === true)
            {
                $this->callbackUrl = $url;

                $this->otpFlow = true;

                return $this->makeOtpCallback($url);
            }

            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }

        if (is_array($request) === true)
        {
            return $this->submitPaymentCallbackRequest($request);
        }

        return $this->submitPaymentCallbackRedirect($request);
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
            if (is_array($content) === true)
            {
                $content['ApprovalCode']      = 'N:-5008:Order does not exist.';
                $content['TransactionResult'] = 'FAILED';
            }

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

    protected function getFailureInVerifyRefund($refundId = 'FakeRfndId')
    {
        $this->mockServerContentFunction(function (& $content, $action = null) use ($refundId)
        {
            if ($action === 'verify_refund')
            {
                $content = str_replace('FakeRfndId', $refundId, $content);
            }
        });
    }

    protected function getSuccessInVerifyRefund()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
    <SOAP-ENV:Header/>
    <SOAP-ENV:Body>
        <ipgapi:IPGApiActionResponse xmlns:a1="http://ipg-online.com/ipgapi/schemas/a1" xmlns:ipgapi="http://ipg-online.com/ipgapi/schemas/ipgapi" xmlns:v1="http://ipg-online.com/ipgapi/schemas/v1">
            <ipgapi:successfully>true</ipgapi:successfully>
            <a1:TransactionValues>
                <v1:CreditCardTxType>
                    <v1:Type>return</v1:Type>
                </v1:CreditCardTxType>
                <v1:CreditCardData>
                    <v1:CardNumber>607093...2841</v1:CardNumber>
                    <v1:ExpMonth>12</v1:ExpMonth>
                    <v1:ExpYear>23</v1:ExpYear>
                    <v1:Brand>RUPAY</v1:Brand>
                </v1:CreditCardData>
                <v1:Payment>
                    <v1:ChargeTotal>3769</v1:ChargeTotal>
                    <v1:Currency>356</v1:Currency>
                </v1:Payment>
                <v1:TransactionDetails>
                    <v1:InvoiceNumber>8klInV4YrI8NBZ</v1:InvoiceNumber>
                    <v1:OrderId>8klInV4YrI8NBZ</v1:OrderId>
                    <v1:MerchantTransactionId>8tUoNltx1QiQpZ</v1:MerchantTransactionId>
                    <v1:TDate>1510529772</v1:TDate>
                </v1:TransactionDetails>
                <ipgapi:IPGApiOrderResponse>
                    <ipgapi:ApprovalCode>Y:30084:Everything is awesome</ipgapi:ApprovalCode>
                    <ipgapi:Brand>RUPAY</ipgapi:Brand>
                    <ipgapi:Country>IND</ipgapi:Country>
                    <ipgapi:OrderId>8klInV4YrI8NBZ</ipgapi:OrderId>
                    <ipgapi:IpgTransactionId>65699567616</ipgapi:IpgTransactionId>
                    <ipgapi:PayerSecurityLevel>y</ipgapi:PayerSecurityLevel>
                    <ipgapi:PaymentType>CREDITCARD</ipgapi:PaymentType>
                    <ipgapi:ReferencedTDate>1510529772</ipgapi:ReferencedTDate>
                    <ipgapi:TDate>1510529772</ipgapi:TDate>
                    <ipgapi:TDateFormatted>2017.11.13 05:06:12 (IST)</ipgapi:TDateFormatted>
                    <ipgapi:TerminalID>87000265</ipgapi:TerminalID>
                </ipgapi:IPGApiOrderResponse>
                <a1:Brand>RUPAY</a1:Brand>
                <a1:TransactionType>RETURN</a1:TransactionType>
                <a1:TransactionState>CAPTURED</a1:TransactionState>
                <a1:UserID>1</a1:UserID>
                <a1:SubmissionComponent>API</a1:SubmissionComponent>
            </a1:TransactionValues>
        </ipgapi:IPGApiActionResponse>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
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

    protected function getErrorTransactionTimedout($error_code)
    {
        $this->mockServerContentFunction(function (& $content) use ($error_code)
        {
            if (is_array($content) === true)
            {
                $content['approval_code'] =  $error_code . ':Transaction timed out';
                $content['status']        = 'DECLINED';
            }
        });
    }
}
