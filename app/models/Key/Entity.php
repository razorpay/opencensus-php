<?php

namespace Models\DAL;

use Constants\Field;

class Key extends UniqueIdDal
{
    protected $table  = \Constants\Table::KEY;

    protected $fillable = array(
        Field\Key::ID,
        Field\Common::MERCHANT_ID,
        Field\Key::SECRET,
        'live',
        Field\Key::ACTIVE
    );

    /**
     * 86400 sec or more accurately 24 hours.
     * When a key is rolled over, by default
     * the old key remains valid for 24 hours.
     */
    const DEFAULT_KEY_EXPIRY_TIME_ON_ROLL = 86400;

    protected $hidden = array(
        Field\Key::SECRET,
    );

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query)
        {
            $query->where(Field\Key::EXPIRED_AT, '=', NULL)
                  ->orWhere(Field\Key::EXPIRED_AT, '>', time());
        });
    }

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(Field\Common::MERCHANT_ID,'=',$merchantId);
    }

    public function setExpired($time = self::DEFAULT_KEY_EXPIRY_TIME_ON_ROLL)
    {
        $this->setAttribute(Field\Key::EXPIRED_AT, time() + $time);
    }

    public function merchant()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant');
    }

    public static function getKeysForMerchant($merchantId, $expired = false)
    {
        $query = self::MerchantId($merchantId);

        $query = ($expired === true) ?: $query->notExpired();

        return $query->get();
    }

    public static function findNotExpired($keyId)
    {
        return self::notExpired()->find($keyId);
    }

    public function getSecret()
    {
        return $this->getAttribute(Field\Key::SECRET);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(Field\Common::MERCHANT_ID);
    }
}
