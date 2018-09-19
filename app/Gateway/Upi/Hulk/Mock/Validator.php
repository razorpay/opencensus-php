<?php

namespace RZP\Gateway\Upi\Hulk\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'type'                  => 'required|string|in:pull,push',
        'amount'                => 'required|integer|min:100',
        'currency'              => 'required|string|in:INR',
        'receiver_id'           => 'sometimes|string|size:18',
        'expire_at'             => 'sometimes_if:type,pull|epoch',
        'sender'                => 'sometimes|array',
        'sender.address'        => 'string|max:255',
        'description'           => 'required|string|max:255',
        'notes'                 => 'sometimes|array',
        'merchant_reference_id' => 'required|string|size:14',
        'category_code'         => 'sometimes|string|size:4',
    ];

    protected static $verifyRules = [
        'id'                    => 'required_without:merchant_reference_id|string|max:18',
        'merchant_reference_id' => 'required_without:id|string|size:14',
    ];

    protected static $authorizeIntentRules = [
        'type'                  => 'required|string|in:expected_push',
        'amount'                => 'required|integer|min:100',
        'currency'              => 'required|string|in:INR',
        'receiver_id'           => 'sometimes|string|size:18',
        'description'           => 'required|string|max:255',
        'notes'                 => 'sometimes|array',
        'caller_account_number' => 'sometimes|max:50',
        'merchant_reference_id' => 'required|string|size:14',
        'category_code'         => 'sometimes|string|size:4',
    ];
}
