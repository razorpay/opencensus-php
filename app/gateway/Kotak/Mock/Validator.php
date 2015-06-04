<?php

namespace Gateway\Kotak\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'TxnType'           => 'required|in:01',
        'TxnRefNo'          => 'required|alpha_num|size:14',
        'Amount'            => 'required|numeric',
        'Currency'          => 'required|in:356',
        'ReturnURL'         => 'required|url',
        'CardNumber'        => 'required|numeric|luhn|digits_between:12,19',
        'ExpiryDate'        => 'required|digits:4|numeric',
        'CardSecurityCode'  => 'required|numeric|digits_between:2,4',
        'MCC'               => 'required|numeric|digits:4',
        'MerchantName'      => 'required|alpha_space_num|min:1|max:25',
        'MerchantCity'      => 'required|max:13',
        'MerchantState'     => 'required|max:2',
        'MerchPostalCode'   => 'required|max:8',
        'MerchPhone'        => 'required|max:12',
        'SecureHash'        => 'required|alpha_num|size:64',
        'MerchantId'        => 'required|alpha_num|max:15',
        'PassCode'          => 'required|alpha_num|size:8',
        'OrderInfo'         => 'sometimes|max:34',
        'TerminalId'        => 'required|alpha_num|size:8',
    );
}
