<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $validateVpaRules = [
        // Bank side Merchant Id
        'required|alpha_num|max:16',
        // Random 10 length string
        'required|alpha_num|size:12',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // RZP API Payment Id - used as customer ID for bank
        'required|alpha_num|max:50',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
    ];

    protected static $authRules = [
        // Bank side Merchant Id
        'required|alpha_num|max:16',
        // Random 10 length string
        'required|alpha_num|size:12',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // RZP API Payment Id - used as customer ID for bank
        'required|alpha_num|max:50',
        // Amount
        ['required', 'regex:/^\d*(\.\d{2})$/'],
        // Description
        'required|max:255',
        // Currency
        'required|alpha_num|max:12',
        // Orderid string
        'required|alpha_num|max:12',
        // VPA
        'required|string|max:255',
        // Timeout
        'required|integer|max:45|min:1',
        // SID
        'required|max:255',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
        'sometimes|in:NA',
    ];

    protected static $verifyRules = [
        // Bank side Merchant Id
        'required|alpha_num',
        // RZP API Payment Id
        'required|alpha_num|max:50',
        // UPI Transaction Reference Id
        'sometimes',
        // Reference Id (Optional, empty string as of now)
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
        'sometimes',
    ];
}
