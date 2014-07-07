<?php

namespace Models\Key;

use Constants\Field;

class Key extends UniqueIdDal
{
    const ID = Common::ID;

    const NAME = 'name';

    const SECRET = 'secret';

    const ACTIVE = 'active';

    const EXPIRED_AT = 'expired_at';

    protected $table  = \Constants\Table::KEY;

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::SECRET,
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
        self::SECRET,
    );

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

    public function setExpired($time = self::DEFAULT_KEY_EXPIRY_TIME_ON_ROLL)
    {
        $this->setAttribute(self::EXPIRED_AT, time() + $time);
    }

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

    protected function generateId($input)
    {
        $this->setAttribute('id', $input['key_id']);
    }
}
