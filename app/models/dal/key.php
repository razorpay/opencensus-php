<?php

namespace Models\DAL;

use \Validator;

class Key extends DAL
{
    protected $fillable = array(
        'id',
        'merchant_id',
        'secret',
        'live',
        'active'
    );

    protected $table  = 'keys';

    public function merchant()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant');
    }
}
