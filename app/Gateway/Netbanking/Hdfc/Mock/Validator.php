<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'ClientCode'            => 'required|alpha_num|max:40',
        'MerchantCode'          => 'required|alpha_num|max:12',
        'TxnCurrency'           => 'required|in:INR',
        'TxnAmount'             => 'required|numeric|max:999999999999999',
        'TxnScAmount'           => 'required|in:0',
        'MerchantRefNo'         => 'required|alpha_num|size:14',
        'SuccessStaticFlag'     => 'required|in:N',
        'FailureStaticFlag'     => 'required|in:N',
        'Date'                  => 'required|date_format:"d/m/Y H:i:s"',
        'DynamicUrl'            => 'required|url',
        'CheckSum'              => 'required',
        'ClientAccNum'          => 'sometimes|string|max:14',
    );

    protected static $verifyRules = array(
        'MerchantCode'          => 'required|alpha_num|max:12',
        'Date'                  => 'required|date_format:"d/m/Y H:i:s"',
        'MerchantRefNo'         => 'required|alpha_num|size:14',
        'TransactionId'         => 'required|in:XTXTV01',
        'FlgVerify'             => 'required|in:Y,V',
        'ClientCode'            => 'required|alpha_num|max:40',
        'SuccessStaticFlag'     => 'required|in:N',
        'FailureStaticFlag'     => 'required|in:N',
        'TxnAmount'             => 'required|numeric|max:999999999999999',
    );
}
