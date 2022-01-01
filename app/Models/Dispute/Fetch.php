<?php

namespace RZP\Models\Dispute;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Payment\Entity as Payment;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::STATUS             => 'sometimes|string',
            Entity::PAYMENT_ID         => 'sometimes|string|size:18',
            Entity::PHASE              => 'sometimes|string',
            Entity::AMOUNT             => 'sometimes|integer',
            Entity::MERCHANT_ID        => 'sometimes|alpha_num',
            Entity::INTERNAL_STATUS    => 'sometimes|string',
            self::EXPAND_EACH          => 'filled|string|in:payment,transaction.settlement',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::INTERNAL_STATUS             => 'sometimes|string',
            Entity::INTERNAL_RESPOND_BY_FROM    => 'sometimes|epoch',
            Entity::INTERNAL_RESPOND_BY_TO      => 'sometimes|epoch',
            Entity::ORDER_BY_INTERNAL_RESPOND   => 'sometimes|boolean',
            Payment::GATEWAY                    => 'sometimes|string',
            Entity::GATEWAY_DISPUTE_SOURCE      => 'sometimes|string|in:customer,network',
            Entity::DEDUCTION_REVERSAL_AT_SET   => 'sometimes|boolean',
            Entity::DEDUCTION_REVERSAL_AT_FROM  => 'sometimes|epoch',
            Entity::DEDUCTION_REVERSAL_AT_TO    => 'sometimes|epoch',
            Entity::DEDUCT_AT_ONSET             => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::STATUS,
            Entity::PAYMENT_ID,
            Entity::PHASE,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::AMOUNT,
            Entity::MERCHANT_ID,
            Entity::INTERNAL_STATUS,
        ],
        AuthType::ADMIN_AUTH => [
            self::EXPAND_EACH,
            Entity::INTERNAL_STATUS,
            Entity::INTERNAL_RESPOND_BY_FROM,
            Entity::INTERNAL_RESPOND_BY_TO,
            Entity::ORDER_BY_INTERNAL_RESPOND,
            Payment::GATEWAY,
            Entity::GATEWAY_DISPUTE_SOURCE,
            Entity::DEDUCTION_REVERSAL_AT_SET,
            Entity::DEDUCTION_REVERSAL_AT_FROM,
            Entity::DEDUCTION_REVERSAL_AT_TO,
            Entity::DEDUCT_AT_ONSET,
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH,
        ],
    ];


    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
    ];
}
