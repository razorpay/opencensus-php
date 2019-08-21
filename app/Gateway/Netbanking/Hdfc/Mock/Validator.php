<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Hdfc\Fields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'ClientCode'                    => 'required|alpha_num|max:40',
        'MerchantCode'                  => 'required|alpha_num|max:12',
        'TxnCurrency'                   => 'required|in:INR',
        'TxnAmount'                     => 'required|numeric|max:999999999999999',
        'TxnScAmount'                   => 'required|in:0',
        'MerchantRefNo'                 => 'required|alpha_num|size:14',
        'SuccessStaticFlag'             => 'required|in:N',
        'FailureStaticFlag'             => 'required|in:N',
        'Date'                          => 'required|date_format:"d/m/Y H:i:s"',
        'DynamicUrl'                    => 'required|url',
        'CheckSum'                      => 'required',
        Fields::CLIENT_ACCOUNT_NUMBER   => 'sometimes|string|max:14',
        Fields::REF1                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha_num|max:14',
        Fields::REF2                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string|max:40',
        Fields::REF3                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha_num|between:5,20',
        Fields::REF4                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string|max:20',
        Fields::REF5                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string|max:20',
        Fields::REF6                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha_num|max:20',
        Fields::REF7                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha_num|max:20',
        Fields::REF8                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha_num|max:15',
        Fields::REF9                    => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|alpha|in:Maximum',
        Fields::REF10                   => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string',
        Fields::DATE1                   => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|date_format:"dmY"',
        Fields::DATE2                   => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|date_format:"dmY"',
        Fields::DISPLAY_DETAILS         => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|in:Y',
        Fields::DETAILS1                => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string',
        Fields::DETAILS2                => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string',
        Fields::DETAILS3                => 'required_with:' . Fields::CLIENT_ACCOUNT_NUMBER . '|string',
    ];

    protected static $verifyRules = [
        'MerchantCode'          => 'required|alpha_num|max:12',
        'Date'                  => 'required|date_format:"d/m/Y H:i:s"',
        'MerchantRefNo'         => 'required|alpha_num|size:14',
        'TransactionId'         => 'required|in:XTXTV01',
        'FlgVerify'             => 'required|in:Y,V',
        'ClientCode'            => 'required|alpha_num|max:40',
        'SuccessStaticFlag'     => 'required|in:N',
        'FailureStaticFlag'     => 'required|in:N',
        'TxnAmount'             => 'required|numeric|max:999999999999999',
        Fields::REF1            => 'required',
    ];
}
