<?php

namespace RZP\Models\Upi\Device;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID           = 'id';
    const TAG          = 'tag';
    const CUSTOMER_ID  = 'customer_id';
    const TOKEN        = 'token';
    const VERIFIED     = 'verified';
    const MOBILE       = 'mobile';
    const TYPE         = 'type';
    const OS           = 'os';
    const APP          = 'app';
    const CAPABILITY   = 'capability';

    protected $fillable = array(
        self::ID,
        self::TAG,
        self::CUSTOMER_ID,
        self::DEVICE_TOKEN,
        self::VERIFIED,
        self::MOBILE,
        self::TYPE,
        self::OS,
        self::APP,
        self::CAPABILITY,
    );

    protected $entity = 'upi_device';

    // ----------------------- Getters ---------------------------------------------

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function isVerified()
    {
        return $this->getAttribute(self::VERIFIED);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }
}
