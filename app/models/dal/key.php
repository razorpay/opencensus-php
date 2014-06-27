<?php

namespace Models\DAL;

use Constants\Field;

class Key extends DAL
{
    protected $table  = \Constants\Table::KEY;

    protected $fillable = array(
        Field\Key::ID,
        Field\Common::MERCHANT_ID,
        Field\Key::SECRET,
        'live',
        Field\Key::ACTIVE
    );

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

    public function setExpired($time = 86400)
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

    public static function findNotExpired($key_id)
    {
        return self::where(Field\Key::ID,'=',$key_id)->notExpired()->first();
    }

    public function getMerchantId()
    {
        return $this->getAttribute(Field\Common::MERCHANT_ID);
    }
}
