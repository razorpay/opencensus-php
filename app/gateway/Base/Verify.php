<?php

namespace Gateway\Base;

class Verify
{
    public $input;

    public $payment;

    public $verifyRequest;

    public $verifyResponse;

    public $verifyResponseContent;

    public $gateway;

    public $status = null;

    public $apiSuccess = null;

    public $gatewaySuccess = null;

    public $throwExceptionOnMismatch = true;

    public function __construct($gateway, array $input, $payment)
    {
        $this->input = $input;

        $this->gateway = $gateway;

        $this->payment = $payment;
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

    public function
}