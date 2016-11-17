<?php

namespace RZP\Models\Upi\Device;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID           = 'id';
    const DEVICE_ID    = 'device_id';
    const CUSTOMER_ID  = 'customer_id';
    const DEVICE_TOKEN = 'device_token';
    const VERIFIED     = 'verified';
    const MOBILE       = 'mobile';
    const GEOCODE      = 'geocode';
    const LOCATION     = 'location';
    const IP           = 'ip';
    const TYPE         = 'type';
    const OS           = 'os';
    const APP          = 'app';
    const CAPABILITY   = 'capability';

    protected $fillable = array(
        self::ID,
        self::DEVICE_ID,
        self::CUSTOMER_ID,
        self::DEVICE_TOKEN,
        self::VERIFIED,
        self::MOBILE,
        self::GEOCODE,
        self::LOCATION,
        self::IP,
        self::TYPE,
        self::OS,
        self::APP,
        self::CAPABILITY,
    );

    protected $entity = 'upi_device';

    // ----------------------- Getters ---------------------------------------------

    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function isVerified()
    {
        return $this->getAttribute(self::VERIFIED);
    }

    public function getDeviceToken()
    {
        return $this->getAttribute(self::DEVICE_TOKEN);
    }
}
