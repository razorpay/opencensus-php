<?php

namespace Models\Customer\App;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       =       'merchant_id';
    const CUSTOMER_ID       =       'customer_id';
    const DEVICE_ID         =       'device_id';
    const APP_ID            =       'app_id';

    protected static $sign      = '';

    protected $entity           = 'customer';

    protected $table            = \Constants\Table::CUSTOMER_APP;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::APP_ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::APP_ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $public = array(
        self::APP_ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('Models\Customer\Entity');
    }

    public function getAppId()
    {
        return $this->getAttribute(self::APP_ID);
    }

    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}