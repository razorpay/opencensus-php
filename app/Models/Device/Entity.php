<?php

namespace RZP\Models\Device;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const TYPE                  = 'type';
    const OS                    = 'os';
    const OS_VERSION            = 'os_version';
    const IMEI                  = 'imei';
    const TAG                   = 'tag';
    const CHALLENGE             = 'challenge';
    const CAPABILITY            = 'capability';
    const PACKAGE_NAME          = 'package_name';
    const CUSTOMER_ID           = 'customer_id';
    const TOKEN_ID              = 'token_id';
    const STATUS                = 'status';
    const VERIFICATION_TOKEN    = 'verification_token';
    const UPI_TOKEN             = 'upi_token';

    protected static $sign = 'dev';

    protected $generateIdOnCreate = true;

    protected $entity = 'device';

    protected $fillable = [
        self::TYPE,
        self::OS,
        self::OS_VERSION,
        self::IMEI,
        self::TAG,
        self::CHALLENGE,
        self::CAPABILITY,
        self::PACKAGE_NAME,
    ];

    protected $defaults = [
        self::STATUS => Status::CREATED,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::OS,
        self::OS_VERSION,
        self::IMEI,
        self::TAG,
        self::CHALLENGE,
        self::CAPABILITY,
        self::PACKAGE_NAME,
        self::STATUS,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
    ];

    protected static $generators = [
        self::VERIFICATION_TOKEN
    ];

    // ----------------------- Getters -----------------------

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    // ----------------------- Generators -----------------------

    protected function generateVerificationToken()
    {
        $verificationToken = (bin2hex(openssl_random_pseudo_bytes(20)));

        if ($verificationToken === false)
        {
            // TODO: Throw exception
        }

        $this->setAttribute(self::VERIFICATION_TOKEN, $verificationToken);
    }
}
