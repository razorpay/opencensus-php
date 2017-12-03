<?php

namespace RZP\Gateway\Upi\Mindgate\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $validateVpaRules = [
        // Bank side Merchant Id
        'required|alpha_num|max:16',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // VPA
        'required|string|max:255',
        // Request Type
        'required|string|in:T',
    ];

    protected static $authRules = [
        // Bank side Merchant Id
        'required|alpha_num',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // VPA
        'required|max:255',
        // Amount
        ['required', 'regex:/^\d*(\.\d{2})$/'],
        // Remark
        'required|string|max:50',
        // Timeout
        'required|integer|max:45|min:1',
        // MCC
        'required|integer|max:9999|min:0'
    ];

    protected static $verifyRules = [
        // Bank side Merchant Id
        'required|alpha_num',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // UPI Transaction Reference Id
        'required|digits_between:3,18',
        // Reference Id (Optional, empty string as of now)
        'sometimes'
    ];
}
