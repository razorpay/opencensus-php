<?php

namespace RZP\Gateway\Hdfc;

use RZP\Models\Base;
use RZP\Exception;

class ResponseXml extends Base\Entity
{
    protected $guarded = array();

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', 'payment_id', 'id');
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
