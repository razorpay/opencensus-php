<?php

namespace RZP\Models\BankTransfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PAYMENT_ID         => 'sometimes|string|min:14|max:18',
            Entity::MERCHANT_ID        => 'sometimes|alpha_num|size:14',
            Entity::PAYER_ACCOUNT      => 'sometimes|string|max:20',
            Entity::PAYER_IFSC         => 'sometimes|string|max:15',
            Entity::PAYEE_ACCOUNT      => 'sometimes|string|max:20',
            Entity::PAYEE_IFSC         => 'sometimes|string|size:11',
            Entity::VIRTUAL_ACCOUNT_ID => 'sometimes|string|min:14|max:17',
            Entity::AMOUNT             => 'sometimes|integer',
            Entity::MODE               => 'sometimes|string|max:4',
            Entity::UTR                => 'sometimes|alpha_num|max:22',
            Entity::REFUND_ID          => 'sometimes|string|min:14|max:19',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::PAYMENT_ID,
            Entity::MERCHANT_ID,
            Entity::PAYER_ACCOUNT,
            Entity::PAYER_IFSC,
            Entity::PAYEE_ACCOUNT,
            Entity::PAYEE_IFSC,
            Entity::VIRTUAL_ACCOUNT_ID,
            Entity::AMOUNT,
            Entity::MODE,
            Entity::UTR,
            Entity::REFUND_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
        Entity::REFUND_ID,
    ];
}
