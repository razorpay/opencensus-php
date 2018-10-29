<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Payment\Gateway;

class ScroogeResponse
{
    /**
     * @var bool
     */
    protected $success;

    /**
     * @var string
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $gatewayResponse = '';

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
    public function getGatewayResponse(): string
    {
        return $this->gatewayResponse;
    }

    /**
     * @param string $gatewayResponse
     * @return ScroogeResponse
     */
    public function setGatewayResponse(array $gatewayResponse): self
    {
        $this->gatewayResponse = json_encode($gatewayResponse);

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
     * @return array
     */
    public function toArray()
    {
        $statusCode = ($this->isSuccess() === true) ? 'REFUND_SUCCESSFUL' : $this->getStatusCode();

        return [
            Gateway::SUCCESS            => $this->isSuccess(),
            Gateway::STATUS_CODE        => $statusCode,
            Gateway::GATEWAY_RESPONSE   => $this->getGatewayResponse(),
            Gateway::GATEWAY_KEYS       => $this->getGatewayKeys()
        ];
    }
}
