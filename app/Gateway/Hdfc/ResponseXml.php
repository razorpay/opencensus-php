<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Models\Base;

class ResponseXml extends Base\Entity
{
    protected $table = 'hdfc_response_xml';

    protected $guarded = array();

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('Models\Payment\Entity', 'payment_id', 'id');
    }
}