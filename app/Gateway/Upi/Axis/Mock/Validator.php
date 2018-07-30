<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'merchId' => 'required|alpha_num|size:9',
        'merchChanId' => 'required|alpha_num|size:12',
        'unqTxnId' => 'required|string|max:255',
        'unqCustId' => 'required|string|max:255',
        'amount' => ['required', 'regex:/^\d*(\.\d{2})$/'],
        'txnDtl' => 'required|string|max:255',
        'currency' => 'required|string|max:255',
        'orderId' => 'required|string|max:255',
        'customerVpa' => 'sometimes|max:255',
        'expiry' => 'required|string|max:255',
        'sId' => 'sometimes|string|max:255',
        'checkSum' => 'required|string|max:10000',
    ];

    protected static $verifyRules = [
        'merchid' => 'required|alpha_num|size:9',
        'merchchanid' => 'required|alpha_num|size:12',
        'tranid' => 'required|string|max:255',
        'mobilenumber' => 'required|string|max:12',
        'checksum' => 'required|string|max:10000',
    ];

}
