<?php

namespace RZP\Models\DeviceDetail\Attribution;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const USER_ID                = 'user_id';
    const APPSFLYER_ID           = 'appsflyer_id';

    const INSTALL_TIME                  = 'install_time';
    const EVENT_TYPE                    = 'event_type';
    const EVENT_TIME                    = 'event_time';

    const CAMPAIGN_ATTRIBUTES           = 'campaign_attributes';
    const CONTRIBUTOR_1_ATTRIBUTES      = 'contributor_1_attributes';
    const CONTRIBUTOR_2_ATTRIBUTES      = 'contributor_2_attributes';
    const CONTRIBUTOR_3_ATTRIBUTES      = 'contributor_3_attributes';

    const DEVICE_TYPE                   = 'device_type';
    const DEVICE_CATEGORY               = 'device_category';
    const PLATFORM                      = 'platform';
    const OS_VERSION                    = 'os_version';
    const APP_VERSION                   = 'app_version';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    protected $entity            = 'app_attribution_detail';

    protected $generateIdOnCreate = true;


    protected $casts              = [
        self::CONTRIBUTOR_1_ATTRIBUTES  => 'array',
        self::CONTRIBUTOR_2_ATTRIBUTES  => 'array',
        self::CONTRIBUTOR_3_ATTRIBUTES  => 'array',
        self::CAMPAIGN_ATTRIBUTES       => 'array',
    ];

    protected $defaults           = [
        self::CONTRIBUTOR_1_ATTRIBUTES  => [],
        self::CONTRIBUTOR_2_ATTRIBUTES  => [],
        self::CONTRIBUTOR_3_ATTRIBUTES  => [],
        self::CAMPAIGN_ATTRIBUTES       => [],
    ];


    protected $fillable = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::CAMPAIGN_ATTRIBUTES        ,
        self::INSTALL_TIME               ,
        self::EVENT_TYPE                 ,
        self::EVENT_TIME                 ,
        self::CONTRIBUTOR_1_ATTRIBUTES   ,
        self::CONTRIBUTOR_2_ATTRIBUTES   ,
        self::CONTRIBUTOR_3_ATTRIBUTES   ,
        self::DEVICE_TYPE                ,
        self::DEVICE_CATEGORY            ,
        self::PLATFORM                   ,
        self::OS_VERSION                 ,
        self::APP_VERSION                ,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::CAMPAIGN_ATTRIBUTES      ,
        self::INSTALL_TIME               ,
        self::EVENT_TYPE                 ,
        self::EVENT_TIME                 ,
        self::CONTRIBUTOR_1_ATTRIBUTES   ,
        self::CONTRIBUTOR_2_ATTRIBUTES   ,
        self::CONTRIBUTOR_3_ATTRIBUTES   ,
        self::DEVICE_TYPE                ,
        self::DEVICE_CATEGORY            ,
        self::PLATFORM                   ,
        self::OS_VERSION                 ,
        self::APP_VERSION                ,
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

}
