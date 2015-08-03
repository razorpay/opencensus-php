<?php

namespace Gateway\Netbanking\Hdfc\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'ClientCode'            => 'required|alpha_num',
        'MerchantCode'          => 'required|alpha',
        'TxnCurrency'           => 'required|in:INR',
        'TxnAmount'             => 'required|numeric',
        'TxnScAmount'           => 'required|in:0',
        'MerchantRefNo'         => 'required|alpha_num|size:14',
        'SuccessStaticFlag'     => 'required|in:N',
        'FailureStaticFlag'     => 'required|in:N',
        'Date'                  => 'required',
        'DynamicUrl'            => 'required|url',
        'CheckSum'              => 'required',
    );
}
