<?php

namespace Gateway\Base;

class Verify
{
    public $input;

    public $payment;

    public $verifyRequest;

    public $verifyResponse;

    public $verifyResponseBody;

    public $verifyResponseContent;

    public $gateway;

    public $status = null;

    public $apiSuccess = null;

    public $gatewaySuccess = null;

    public $throwExceptionOnMismatch = true;

    public function __construct($gateway, array $input)
    {
        $this->input = $input;

        $this->gateway = $gateway;
    }

    public function setVerifyRequest($request)
    {
        $this->verifyRequest = $request;
    }

    public function setVerifyResponseContent($content)
    {
        $this->verifyResponseContent = $content;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setApiAndGatewaySuccess($apiSuccess, $gatewaySuccess)
    {
        ;
    }

    public function getDataToTrace()
    {
        return array(
            'status'                    => $this->status,
            'gateway'                   => $this->gateway,
            'verifyResponseContent'     => $this->verifyResponseContent,
            'apiSuccess'                => $this->apiSuccess,
            'verifyRequest'             => $this->verifyRequest,
            'gatewaySuccess'            => $this->gatewaySuccess,
            'gatewayPayment'            => $this->payment->toArray(),
        );
    }
}