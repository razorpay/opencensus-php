<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
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

    protected static $verifyRules = array(
        'merchantId'        => 'numeric|max:9999999999',
        'merchantTranId'    => 'required|alpha_num|max:20',
        'subMerchantId'     => 'sometimes|alpha_num|max:10',
        'terminalId'        => 'sometimes|digits_between:1,10'
    );
}
