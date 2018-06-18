<?php

namespace RZP\Models\PaymentLink;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID   => 'filled|alpha_num|size:14',
            Entity::USER_ID       => 'filled|alpha_num|size:14',
            Entity::RECEIPT       => 'filled|string|min:2|max:40',
            Entity::TITLE         => 'filled|string|min:2|max:255',
            Entity::STATUS        => 'filled|required_with:status_reason|custom',
            Entity::STATUS_REASON => 'filled|custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
        ],
        AuthType::PROXY_AUTH => [
            Entity::RECEIPT,
            Entity::TITLE,
            Entity::STATUS,
            Entity::STATUS_REASON,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::USER_ID,
            Entity::MERCHANT_ID,
        ],
    ];

    const SIGNED_IDS = [
    ];

    const ES_FIELDS = [
        Entity::TITLE,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::USER_ID,
        Entity::STATUS,
        Entity::STATUS_REASON,
        Entity::RECEIPT,
    ];

    protected function validateStatus(string $attribute, string $value)
    {
        Status::checkStatus($value);
    }

    protected function validateStatusReason(string $attribute, string $value)
    {
        StatusReason::checkStatusReason($value);
    }
}
