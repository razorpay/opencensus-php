<?php

namespace Models\DAL;

use \Validator;
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

    public function setExpired($time = 86400)
    {
        $this->setAttribute(Field\Key::EXPIRED_AT, time() + $time);
        $this->save();
    }

    public function merchant()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant');
    }

    public static function getKeysForMerchant($merchant_id, $expired = false)
    {
        if ($expired === true)
           return self::where(Field\Common::MERCHANT_ID,'=',$merchant_id)->get();
        else
            return self::where(Field\Common::MERCHANT_ID,'=',$merchant_id)->notExpired()->get();
    }

    public static function retrieve($key_id)
    {
        return self::where(Field\Key::ID,'=',$key_id)->notExpired()->first();
    }
}
