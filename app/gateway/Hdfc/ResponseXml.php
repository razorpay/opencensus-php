<?php

namespace Gateway\Hdfc;

use EE\Exception;
use Models\Base;

class ResponseXml extends Base\Entity
{
    protected $table = 'hdfc_response_xml';

    public $incrementing = false;

    protected $guarded = array();

    public function transaction()
    {
        return $this->belongsTo('Transaction', 'trackid', 'id');
    }
}