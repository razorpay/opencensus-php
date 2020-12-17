<?php

namespace RZP\Models\AppStore;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const ID            = "id";
    const APP_NAME      = 'app_name';
    const MERCHANT_ID   = 'merchant_id';
    const MOBILE_NUMBER = 'mobile_number';

    protected $entity = 'app_store';

    protected $fillable = [
        self::APP_NAME,
        self::ID,
        self::MERCHANT_ID,
        self::MOBILE_NUMBER,
    ];

    protected $public = [
        self::APP_NAME,
        self::ID,
        self::MERCHANT_ID,
        self::MOBILE_NUMBER,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function setAppName(string $appName)
    {
        $this->setAttribute(self::APP_NAME, $appName);
    }

    public function getAppName(): string
    {
        return $this->getAttribute(self::APP_NAME);
    }

    public function setMobileNumber(string $mobileNumber)
    {
        $this->setAttribute(self::MOBILE_NUMBER, $mobileNumber);
    }

    public function getMobileNumber(): string
    {
        return $this->getAttribute(self::MOBILE_NUMBER);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}
