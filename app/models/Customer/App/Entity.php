<?php

namespace Models\Customer\App;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       =       'merchant_id';
    const CUSTOMER_ID       =       'customer_id';
    const DEVICE_ID         =       'device_id';

    protected static $sign      = 'app';

    protected $entity           = 'customer_app';

    protected $table            = \Constants\Table::CUSTOMER_APP;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $public = array(
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

    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }
}