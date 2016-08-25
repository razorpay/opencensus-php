<?php

namespace RZP\Gateway\UPI\ICICI\Mock;

use RZP\Models\Base;
use RZP\Gateway\Wallet\Olamoney;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantId'        =>  'numeric|max:9999999999',
        'merchantName'      =>  'alpha_num|max:50',
        'subMerchantId'     =>  'sometimes|alpha_num|max:10',
        'subMerchantName'   =>  'sometimes|alpha_num|max:50',
        'terminalId'        =>  'sometimes|digits:10',
        'merchantTranId'    =>  'required|alpha_num|max:20',
        'billNumber'        =>  'sometimes|alpha_num|max:50',
        'payerVa'           =>  'required|max:255',
        'amount'            =>  array('required', 'regex:/^\d*(\.\d{2})$/'),
        'note'              =>  'sometimes|string|max:50',
        'collectByDate'     =>  'sometimes|string|max:255'
    );
}
