<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID           = 'merchant_id';
    const CUSTOMER_ID           = 'customer_id';
    const DEVICE_TOKEN          = 'device_token';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'capp';

    protected $entity           = 'app_token';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::DEVICE_TOKEN,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::DEVICE_TOKEN,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    );

    protected $public = array(
        self::DEVICE_TOKEN,
        self::CUSTOMER_ID,
    );

    protected static $generators = array(
        self::DEVICE_TOKEN,
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function getDeviceToken()
    {
        return $this->getAttribute(self::DEVICE_TOKEN);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function generateDeviceToken()
    {
        if ($this->getDeviceToken() === null)
        {
            $deviceToken = self::generateUniqueId();

            $this->setAttribute(self::DEVICE_TOKEN, $deviceToken);
        }
    }
}
