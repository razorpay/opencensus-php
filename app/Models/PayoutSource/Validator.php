<?php

namespace RZP\Models\PayoutSource;

use RZP\Base;

class Validator extends Base\Validator
{
    const PAYOUT_SOURCE_CREATE = 'payout_source_create';

    protected static $createRules = [
        Entity::SOURCE_ID   => 'required|string',
        Entity::SOURCE_TYPE => 'required|string|',
        Entity::PRIORITY    => 'required|integer|min:1'
    ];

    protected static $payoutSourceCreateRules = [
        Entity::SOURCE_ID   => 'required|string',
        Entity::SOURCE_TYPE => 'required|string|',
        Entity::PRIORITY    => 'required|integer|min:1'
    ];
}

