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
}
