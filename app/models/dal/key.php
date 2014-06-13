<?php

namespace Models\DAL;

use \Validator;

class Key extends DAL
{
    protected $table  = 'keys';

    protected $fillable = array(
        'id',
        'merchant_id',
        'secret',
        'live',
        'active'
    );

    protected $hidden = array(
        'secret',
    );

    public function scopeNotExpired($query)
    {
        return $query->where('expired_at', '=', NULL)->orWhere('expired_at', '>', time());
    }

    public function setExpired($time = 86400)
    {
        $this->setAttribute('expired_at', time() + $time);
        $this->save();
    }

    public function merchant()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant');
    }

    public static function getKeysForMerchant($merchant_id, $expired = false)
    {
        if ($expired == true)
            return self::where('merchant_id','=',$merchant_id)->get();
        else
            return self::where('merchant_id','=',$merchant_id)->notExpired()->get();
    }
}
