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
        'sender.address' => 'string|max:255',
        'description'    => 'required|string|max:255',
        'notes'          => 'sometimes|array'
    ];

    protected static $verifyRules = [
        'id'            => 'required|string|max:18',
    ];

    protected static $authorizeIntentRules = [
        'type'                  => 'required|string|in:expected_push',
        'amount'                => 'required|integer|min:100',
        'currency'              => 'required|string|in:INR',
        'description'           => 'required|string|max:255',
        'notes'                 => 'sometimes|array',
        'caller_account_number' => 'sometimes|max:50',
    ];
}
