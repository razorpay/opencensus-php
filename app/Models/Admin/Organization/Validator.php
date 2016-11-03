<?php

namespace RZP\Models\Payment;

use Lib\PhoneBook;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'amount'                  =>  'required|integer',
        'currency'                =>  'required|size:3',
        'method'                  =>  'custom',
        'vpa'                     =>  'required_if:method,upi|max:100|custom',
        'card'                    =>  'sometimes',
        'bank'                    =>  'required_if:method,netbanking',
        'wallet'                  =>  'required_if:method,wallet|custom',
        'emi_duration'            =>  'required_if:method,emi|integer|in:3,6,9,12,18,24',
        'description'             =>  'sometimes',
        'email'                   =>  'required|email',
        'contact'                 =>  'required|contact_syntax',
        'signature'               =>  'sometimes',
        'notes'                   =>  'sometimes|notes',
        'notes.merchant_order_id' =>  'required_with:signature',
        'callback_url'            =>  'sometimes|url',
        'order_id'                =>  'sometimes',
        'customer_id'             =>  'sometimes',
        'app_token'               =>  'sometimes',
        'token'                   =>  'sometimes',
        'save'                    =>  'sometimes|in:0,1',
        'recurring'               =>  'sometimes_if:method,card|in:0,1',
        'fee'                     =>  'sometimes|integer|max:50000000',
        'service_tax'             =>  'sometimes|integer|max:50000000',
        '_'                       =>  'sometimes'
    ];

}
