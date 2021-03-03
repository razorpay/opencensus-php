<?php

namespace RZP\Gateway\Base;

class Verify
{
    public $input;

    /**
     * @var Entity
     */
    public $payment;

    /**
     * Used If the transaction happens via a wallet.
     */
    public $wallet;

    public $verifyRequest;

    public $verifyResponse;

    public $verifyResponseBody;

    public $verifyResponseContent;

    public $gateway;

    public $status = VerifyResult::STATUS_MATCH;

    public $apiSuccess = null;

    public $gatewaySuccess = null;

    public $error = null;

    public $throwExceptionOnMismatch = true;

    /**
     * Used to set the status match property of $verify
     *
     * @var bool
     */
    public $amountMismatch = false;

    public $match;

    public $currency;

    public $amountAuthorized;

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

    public function setVerifyResponseBody($body)
    {
        $this->verifyResponseBody = $body;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setApiAndGatewaySuccess($apiSuccess, $gatewaySuccess)
    {
        ;
    }

    public function setAmountMismatch(bool $amountMismatch)
    {
        $this->amountMismatch = $amountMismatch;
    }

    public function setCurrencyAndAmountAuthorized(string $currency, int $amountAuthorized)
    {
        $this->currency         = $currency;
        $this->amountAuthorized = $amountAuthorized;
    }

    public function getDataToTrace()
    {
        $payment = [];

        if ($this->payment !== null)
        {
            $payment = $this->payment->toArray();
        }

        return array(
            'status'                    => $this->status,
            'error'                     => $this->error,
            'gateway'                   => $this->gateway,
            'verifyResponseContent'     => $this->verifyResponseContent,
            'amountMismatch'            => $this->amountMismatch,
            'apiSuccess'                => $this->apiSuccess,
            'verifyRequest'             => $this->verifyRequest,
            'gatewaySuccess'            => $this->gatewaySuccess,
            'gatewayPayment'            => $payment,
        );
    }
}
