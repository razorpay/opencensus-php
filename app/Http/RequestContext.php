<?php

namespace App\Http;

final class RequestContext
{
    public ?string $merchantId = null;

    public ?string $userId = null;

    public ?bool $oauthRequest = false;

    // if redis sessions have to be overriden by laravel session handler
    // used by CustomCacheBasedSessionHandler write()
    public ?bool $shouldOverrideSession = true;

    /**
     * @return string|null
     */
    public function getMerchantId(): string|null
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     */
    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string|null
     */
    public function getUserId(): string|null
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return bool
     */
    public function isOauthRequest(): bool
    {
        return $this->oauthRequest;
    }

    /**
     * @param bool $oauthRequest
     */
    public function setOauthRequest(bool $oauthRequest): void
    {
        $this->oauthRequest = $oauthRequest;
    }

    public function setShouldOverrideSession(bool $override): void
    {
        $this->shouldOverrideSession = $override;
    }

    public function shouldOverrideSession(): bool
    {
        return $this->shouldOverrideSession;
    }
}
