<?php

namespace App\Http;

final class RequestContext
{
    public $merchantId;

    public $userId;

    public $oauthRequest = false;

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return bool
     */
    public function isOauthRequest()
    {
        return $this->oauthRequest;
    }

    /**
     * @param bool $oauthRequest
     */
    public function setOauthRequest($oauthRequest)
    {
        $this->oauthRequest = $oauthRequest;
    }
}
