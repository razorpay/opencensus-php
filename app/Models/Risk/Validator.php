<?php

namespace RZP\Models\Risk;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID    => 'required|public_id',
        Entity::MERCHANT_ID   => 'required|public_id',
        Entity::FRAUD_TYPE    => 'required|string|max:20|filled',
        Entity::SOURCE        => 'required|string|max:20|filled',
        Entity::MAXMIND_SCORE => 'sometimes|integer',
        Entity::COMMENTS      => 'sometimes|string|max:255', # adding a soft validation
    ];

    protected static $editRules = [
        Entity::PAYMENT_ID    => 'sometimes|public_id',
        Entity::MERCHANT_ID   => 'sometimes|public_id',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
        Entity::SOURCE        => 'sometimes|string|max:20',
        Entity::MAXMIND_SCORE => 'sometimes|integer',
        Entity::COMMENTS      => 'sometimes|string|max:255',
    ];
}
