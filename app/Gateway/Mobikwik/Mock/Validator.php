<?php

namespace RZP\Gateway\Mobikwik\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'mid'           => 'required|alpha_num',
        'orderid'       => 'required|size:14|alpha_num',
        'amount'        => 'required|numeric',
        'cell'          => 'required|numeric',
        'email'         => 'required|email',
        'merchantname'  => 'required|alpha_num',
        'redirecturl'   => 'required|url',
        'showmobile'    => 'sometimes|',
        'version'       => 'sometimes|numeric',
        'checksum'      => 'required',
        'merchantAlias' => 'sometimes',
        'mccCode'       => 'sometimes'
    );

    protected static $refundRules = array(
        'mid'       => 'required|alpha_num',
        'txid'      => 'required|size:14|alpha_num',
        'refundid'  => 'required|size:14|alpha_num',
        'amount'    => 'required|numeric',
        'ispartial' => 'sometimes|alpha_num',
        'checksum'  => 'required',
    );
}
