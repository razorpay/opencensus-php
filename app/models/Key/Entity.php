<?php

namespace Models\Key;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';

    const MERCHANT_ID = 'merchant_id';

    /**
     * This is the secret used for authenticating
     * merchant's server side requests.
     * It's actually a hash of the actual secret
     */
    const SECRET = 'secret';

    const ACTIVE = 'active';

    const EXPIRED_AT = 'expired_at';

    const KEY_SECRET_HASH_LENTH = 100;

    protected $table  = \Constants\Table::KEY;

    protected $fillable = array(
        self::MERCHANT_ID,
        'live',
        self::ACTIVE
    );

    protected static $generators = array('id');

    /**
     * 86400 sec or more accurately 24 hours.
     * When a key is rolled over, by default
     * the old key remains valid for 24 hours.
     */
    const DEFAULT_KEY_EXPIRY_TIME_ON_ROLL = 86400;

    protected $hidden = array(
        self::SECRET);

    public function merchant()
    {
        return $this->belongsTo(
            '\Models\Merchant\Entity');
    }

    public function getSecret()
    {
        return $this->getAttribute(self::SECRET);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query)
        {
            $query->where(self::EXPIRED_AT, '=', NULL)
                  ->orWhere(self::EXPIRED_AT, '>', time());
        });
    }

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(self::MERCHANT_ID,'=',$merchantId);
    }

    public function isExpiredOrExpiring()
    {
        return ($this->attributes[self::EXPIRED_AT] !== null);
    }

    public function isExpired()
    {
        $expiredAt = $this->getAttribute(self::EXPIRED_AT);

        return ($expiredAt <= time());
    }

    public function checkAndSetExpired($roll = false)
    {
        if ($this->isExpiredOrExpiring())
        {
            $errorCode = null;

            if ($this->isExpired())
                $errorCode = ErrorCode::BAD_REQUEST_KEY_EXPIRED;
            else
                $errorCode = ErrorCode::BAD_REQUEST_KEY_EXPIRING_SOON;

            throw new Exception\BadRequestException(null, $errorCode);
        }

        $this->setExpired($roll);
    }

    public function setExpired($roll = false)
    {
        $time = 0;

        if ($roll === true)
            $time = self::DEFAULT_KEY_EXPIRY_TIME_ON_ROLL;

        $this->setAttribute(self::EXPIRED_AT, time() + $time);
    }

    /**
     * generates the key secret uses a
     * cryptographically strong algorithm.
     * Sets it's hash in object and returns the secret.
     */
    public function generateSecret()
    {
        $len = self::ID_LENGTH;

        $secret = bin2hex(openssl_random_pseudo_bytes($len/2));

        $this->setAttribute(self::SECRET, \Hash::make($secret));

        return $secret;
    }

    public static function generateUniqueId()
    {
        $len = self::ID_LENGTH;

        $id = bin2hex(openssl_random_pseudo_bytes($len/2));

        return $id;
    }
}
