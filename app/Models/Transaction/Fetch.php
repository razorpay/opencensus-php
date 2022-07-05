<?php

namespace RZP\Models\Transaction;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::SETTLED         => 'sometimes|in:0,1',
            Entity::ON_HOLD         => 'sometimes|in:0,1',
            Entity::TYPE            => 'sometimes|in:payment,refund,settlement,adjustment,commission,payout,credit_transfer',
            Entity::SETTLEMENT_ID   => 'sometimes|alpha_dash|min:14|max:19',
            Entity::ENTITY_ID       => 'sometimes|alpha_dash|min:14',
            Entity::MERCHANT_ID     => 'sometimes|alpha_num',
            Entity::RECONCILED      => 'sometimes|in:0,1',
            self::EXPAND_EACH       => 'sometimes|in:settlement',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::SETTLED,
            Entity::ON_HOLD,
            Entity::TYPE,
            Entity::SETTLEMENT_ID,
            Entity::ENTITY_ID,
            Entity::MERCHANT_ID,
            Entity::RECONCILED,
            self::EXPAND_EACH,
        ],
    ];

    protected $signedIds = [
        Entity::SETTLEMENT_ID,
    ];
}
