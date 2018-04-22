<?php

namespace RZP\Gateway\Upi\Hulk\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'type'           => 'required|string|in:pull,push',
        'amount'         => 'required|integer|min:100',
        'currency'       => 'required|string|in:INR',
        'expire_at'      => 'sometimes_if:type,pull|epoch',
        'sender'         => 'sometimes|array',
        'sender.address' => 'string',
        'description'    => 'sometimes|string',
        'notes'          => 'sometimes|array'
    ];

    protected static $verifyRules = [
    ];
}
