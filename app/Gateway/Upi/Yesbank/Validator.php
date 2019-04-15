<?php

namespace RZP\Gateway\Upi\Yesbank;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $payoutRules = [
        'gateway_input.vpa'             => 'required|string',
        'gateway_input.amount'          => 'required',
        'gateway_input.ref_id'          => 'required|string',
        'terminal.gateway_merchant_id'  => 'required|string',
        'merchant.category'             => 'required',
        'gateway_input.narration'       => 'sometimes|string',
        'gateway_input.account_number'  => 'sometimes',
        'gateway_input.ifsc_code'       => 'sometimes',
    ];

    protected static $payoutVerifyRules = [
        'terminal.gateway_merchant_id'  => 'required|string',
        'gateway_input.ref_id'          => 'required|string',
    ];
}
