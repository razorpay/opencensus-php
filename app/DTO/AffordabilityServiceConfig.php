<?php

namespace RZP\DTO;

final class AffordabilityServiceConfig extends DataTransferObject
{
    /** @var string */
    public $baseUrl;

    /** @var string */
    public $secret;

    /** @var string */
    public $mode;

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
