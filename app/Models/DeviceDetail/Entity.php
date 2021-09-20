<?php

namespace RZP\Models\DeviceDetail;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const USER_ID                = 'user_id';
    const APPSFLYER_ID           = 'appsflyer_id';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    protected $entity            = 'user_device_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
    ];

    public function getAppsFlyerId()
    {
        return $this->getAttribute(self::APPSFLYER_ID);
    }
}
