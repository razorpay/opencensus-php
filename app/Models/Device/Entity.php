<?php

namespace RZP\Models\Device;

use RZP\Models\Base;
use Carbon\Carbon;

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
    const VERIFIED_AT           = 'verified_at';
    const REGISTERED_AT         = 'registered_at';
    const CUSTOMER              = 'customer';

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
        self::ENTITY,
        self::TYPE,
        self::OS,
        self::OS_VERSION,
        self::TAG,
        self::CUSTOMER,
        self::STATUS,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
    ];

    protected static $generators = [
        self::VERIFICATION_TOKEN
    ];

    protected $appends = [
        self::PUBLIC_ID,
        self::CUSTOMER,
    ];

    // ----------------------- Getters -----------------------

    public function getVerificationToken()
    {
        return $this->getAttribute(self::VERIFICATION_TOKEN);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function hasBeenRegistered()
    {
        return ($this->getAttribute(self::REGISTERED_AT) !== null);
    }

    public function hasBeenVerified()
    {
        return ($this->getAttribute(self::VERIFIED_AT) !== null);
    }

    // ----------------------- Setters -----------------------

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);

        // Sets corresponding timestamps as per new status
        if (in_array($status, Status::$timestampedStatuses, true))
        {
            $timestampKey = $status . '_at';
            $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setUpiToken($upiToken)
    {
        $this->setAttribute(self::UPI_TOKEN, $upiToken);
    }

    // ----------------------- Accessors -----------------------

    protected function getCustomerAttribute()
    {
        $customer = $this->customer()->getResults();

        if ($customer === null)
        {
            return null;
        }

        return $customer->toArrayPublic();
    }

    // ----------------------- Generators -----------------------

    protected function generateVerificationToken()
    {
        $verificationToken = bin2hex(openssl_random_pseudo_bytes(20));

        if (empty($verificationToken))
        {
            // TODO: Throw exception
        }

        $this->setAttribute(self::VERIFICATION_TOKEN, $verificationToken);
    }

    // ----------------------- Relations -----------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }
}
