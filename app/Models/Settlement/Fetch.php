<?php

namespace RZP\Models\Settlement;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID            => 'sometimes|alpha_num|size:14',
            Entity::BANK_ACCOUNT_ID        => 'sometimes|alpha_dash|min:14|max:17',
            Entity::BATCH_FUND_TRANSFER_ID => 'sometimes|alpha_num|max:14',
            Entity::TRANSACTION_ID         => 'sometimes|alpha_dash|min:14|max:18',
            Entity::STATUS                 => 'sometimes|in:created,initiated,processed,failed',
            Entity::UTR                    => 'sometimes|alpha_num',
            Entity::CHANNEL                => 'sometimes|string',
            Entity::BALANCE_ID             => 'sometimes|alpha_dash|min:14|max:18',
            Entity::SETTLED_BY             => 'sometimes|string',
            Entity::OPTIMIZER_PROVIDER     => 'sometimes|alpha_num|size:14'
        ],
        AuthType::PROXY_AUTH => [
            Entity::UTR                    => 'sometimes|alpha_num',
            Entity::STATUS                 => 'sometimes|in:created,initiated,processed,failed',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::BANK_ACCOUNT_ID,
            Entity::BATCH_FUND_TRANSFER_ID,
            Entity::TRANSACTION_ID,
            Entity::STATUS,
            Entity::UTR,
            Entity::CHANNEL,
            Entity::BALANCE_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::UTR,
            Entity::STATUS
        ],
    ];

    const SIGNED_IDS = [
        Entity::BANK_ACCOUNT_ID,
        Entity::TRANSACTION_ID,
        Entity::BALANCE_ID,
    ];

}
