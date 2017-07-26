<?php

namespace RZP\Models\Risk;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID    => 'required|string|size:14',
        Entity::MERCHANT_ID   => 'required|string|size:14',
        Entity::FRAUD_TYPE    => 'required|string|max:20|filled|in:confirmed,suspected',
        Entity::SOURCE        => 'required|string|max:20|filled',
        Entity::RISK_SCORE    => 'sometimes|integer',
        Entity::COMMENTS      => 'required|string|max:255|filled', # soft validation
    ];

    protected static $editRules = [
        Entity::PAYMENT_ID    => 'sometimes|string|size:14',
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|string|max:20',
        Entity::RISK_SCORE    => 'sometimes|integer',
        Entity::COMMENTS      => 'required|string|filled',
    ];
}
