<?php

namespace Models\Order;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        =>  'required|integer|max:50000000',
        'currency'      =>  'required|size:3|in:INR',
        // 'attempts'      =>  '',
        // 'status'        =>  '',
        'receipt'       =>  'required|string',
    );
}
