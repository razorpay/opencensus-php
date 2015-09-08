<?php

namespace Gateway\Netbanking\Kotak\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'MessageCode'            => 'required|alpha_num',
        'DateTimeInGMT'          => 'required|alpha',
        'MerchantId'             => 'required|in:INR',
        'TraceNumber'            => 'required|numeric',
        'Amount'                 => 'required|in:0',
        'TransactionDescription' => 'required|alpha_num|size:14',
        'CheckSum'               => 'required',
    );

    protected static $verifyRules = array(
        'MerchantCode'      => 'required|',
        'Date'              => 'required|',
        'MerchantRefNo'     => 'required|',
        'TransactionId'     => 'required|in:XTXTV01',
        'FlgVerify'         => 'required|in:Y',
        'ClientCode'        => 'required|',
        'SuccessStaticFlag' => 'required|in:N',
        'FailureStaticFlag' => 'required|in:N',
        'TxnAmount'         => 'required|numeric',
    );
}
