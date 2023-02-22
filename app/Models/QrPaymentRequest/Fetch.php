<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::QR_CODE_ID            => 'sometimes|string',
            Entity::TRANSACTION_REFERENCE => 'sometimes',
            Entity::MERCHANT_ID           => 'sometimes|alpha_num|size:14',
            Entity::IS_CREATED            => 'sometimes|boolean',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::QR_CODE_ID,
            Entity::TRANSACTION_REFERENCE,
            Entity::IS_CREATED,
        ],
    ];

    const ES_FIELDS = [
        Entity::MERCHANT_ID,
    ];

    const SIGNED_IDS = [
        Entity::QR_CODE_ID,
    ];

    const COMMON_FIELDS = [
    ];
}
