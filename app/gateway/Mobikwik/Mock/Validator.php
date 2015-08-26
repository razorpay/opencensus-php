<?php

namespace Gateway\Mobikwik\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'mid'          => 'required|alpha_num',
        'orderid'      => 'required|size:14|alpha_num',
        'amount'       => 'required|numeric',
        'cell'         => 'required|numeric',
        'email'        => 'required|email',
        'merchantname' => 'required|alpha_num',
        'redirecturl'  => 'sometimes|url',
        'showmobile'   => 'sometimes|',
        'version'      => 'sometimes|numeric',
        'checksum'     => 'sometimes'
    );

    protected static $refundRules = array(
        'mid'       => 'required|alpha_num',
        'txid'      => 'required|size:14|alpha_num',
        'email'     => 'required|email',
        'amount'    => 'required|numeric',
        'ispartial' => 'sometimes|alpha_num',
        'checksum'  => 'required',
    );
}
