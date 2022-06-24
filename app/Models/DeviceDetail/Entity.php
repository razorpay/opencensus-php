<?php

namespace RZP\Models\DeviceDetail;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const USER_ID                = 'user_id';
    const APPSFLYER_ID           = 'appsflyer_id';
    /*
     * added for identifying mobile app mtu transactions
     */
    const SIGNUP_SOURCE          = 'signup_source';
    const SIGNUP_CAMPAIGN        = 'signup_campaign';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    const METADATA               = 'metadata';

    protected $entity            = 'user_device_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::SIGNUP_SOURCE,
        self::SIGNUP_CAMPAIGN,
        self::METADATA
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::SIGNUP_SOURCE,
        self::SIGNUP_CAMPAIGN,
        self::METADATA
    ];

    protected $casts = [
        self::METADATA => 'array',
    ];

    protected $defaults = [
        self::METADATA => []
    ];

    public function getAppsFlyerId()
    {
        return $this->getAttribute(self::APPSFLYER_ID);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getSignupSource()
    {
        return $this->getAttribute(self::SIGNUP_SOURCE);
    }

    public function getSignupCampaign()
    {
        return $this->getAttribute(self::SIGNUP_CAMPAIGN);
    }

    public function getMetaData()
    {
        return $this->getAttribute(self::METADATA);
    }

    public function getValueFromMetaData($key){
        $metaData   = $this->getAttribute(self::METADATA);

        $value = null;

        if (empty($metaData) === false
            and array_key_exists($key, $metaData))
        {
            $value = $metaData[$key];
        }

        return $value;
    }
}
