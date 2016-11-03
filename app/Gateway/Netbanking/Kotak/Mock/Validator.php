<?php

namespace RZP\Gateway\Netbanking\Kotak\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'MessageCode'            => 'required|alpha_num',
        'DateTimeInGMT'          => 'required',
        'MerchantId'             => 'required',
        'TraceNumber'            => 'required|numeric',
        'Amount'                 => 'required',
        'TransactionDescription' => 'required',
        'Checksum'               => 'required',
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
