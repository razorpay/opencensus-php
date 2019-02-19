<?php

namespace RZP\Gateway\Base;

use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Refund\Status;

class ScroogeResponse
{
    /**
     * @var bool
     */
    protected $success;

    /**
     * @var string
     */
    protected $statusCode = '';

    /**
     * @var string
     */
    protected $refundGateway = '';

    /**
     * @var string
     */
    protected $gatewayResponse = '';

    /**
     * @var string
     */
    protected $gatewayVerifyResponse = '';

    /**
     * @var array
     */
    protected $gatewayKeys = [];

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return ScroogeResponse
     */
    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @param string $statusCode
     * @return ScroogeResponse
     */
    public function setStatusCode(string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefundGateway(): string
    {
        return $this->refundGateway;
    }

    /**
     * @param string $refundGateway
     * @return ScroogeResponse
     */
    public function setRefundGateway(string $refundGateway): self
    {
        $this->refundGateway = $refundGateway;

        return $this;
    }

    /**
     * @return string
     */
    public function getGatewayResponse(): string
    {
        return $this->gatewayResponse;
    }

    /**
     * @param string $gatewayResponse
     * @return ScroogeResponse
     */
    public function setGatewayResponse($gatewayResponse): self
    {
        $this->gatewayResponse = (is_string($gatewayResponse) === false) ?
                                  json_encode($gatewayResponse) :
                                  $gatewayResponse;

        return $this;
    }

    /**
     * @return array
     */
    public function getGatewayKeys(): array
    {
        return $this->gatewayKeys;
    }

    /**
     * @param array $gatewayKeys
     * @return ScroogeResponse
     */
    public function setGatewayKeys(array $gatewayKeys): self
    {
        $this->gatewayKeys = $gatewayKeys;

        return $this;
    }

    /**
     * @return string
     */
    public function getGatewayVerifyResponse(): string
    {
        return $this->gatewayVerifyResponse;
    }

    /**
     * @param  $gatewayVerifyResponse
     * @return ScroogeResponse
     */
    public function setGatewayVerifyResponse($gatewayVerifyResponse): self
    {
        $this->gatewayVerifyResponse = (is_string($gatewayVerifyResponse) === false) ?
                                        json_encode($gatewayVerifyResponse) :
                                        $gatewayVerifyResponse;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $statusCode = ($this->isSuccess() === true) ? 'REFUND_SUCCESSFUL' : $this->getStatusCode();

        return [
            Gateway::SUCCESS                    => $this->isSuccess(),
            Gateway::STATUS_CODE                => $statusCode,
            Gateway::REFUND_GATEWAY             => $this->getRefundGateway(),
            Gateway::GATEWAY_VERIFY_RESPONSE    => $this->getGatewayVerifyResponse(),
            Gateway::GATEWAY_RESPONSE           => $this->getGatewayResponse(),
            Gateway::GATEWAY_KEYS               => $this->getGatewayKeys()
        ];
    }
}
