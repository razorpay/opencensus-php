<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS       => [
            Entity::STATUS                 => 'sometimes|in:active,closed',
            Entity::MERCHANT_ID            => 'sometimes|alpha_num|size:14',
            Entity::NOTES                  => 'sometimes|notes_fetch',
            Entity::NAME                   => 'sometimes|string',
            Entity::CUSTOMER_ID            => 'sometimes|string',
            EsRepository::CUSTOMER_EMAIL   => 'sometimes|string',
            EsRepository::CUSTOMER_NAME    => 'sometimes|string',
            EsRepository::CUSTOMER_CONTACT => 'sometimes|string',
            Entity::ENTITY_TYPE            => 'sometimes|string',
            Entity::USAGE_TYPE             => 'sometimes|string',
            Entity::PROVIDER               => 'sometimes|string'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::USAGE_TYPE,
            Entity::PROVIDER,
        ],
        AuthType::PRIVATE_AUTH   => [
            Entity::STATUS,
            Entity::NOTES,
            Entity::NAME,
            Entity::PROVIDER,
            Entity::CUSTOMER_ID,
            EsRepository::CUSTOMER_EMAIL,
            EsRepository::CUSTOMER_NAME,
            EsRepository::CUSTOMER_CONTACT,
            Entity::ENTITY_TYPE,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
    ];

    const ES_FIELDS = [
        Entity::STATUS,
        Entity::NOTES,
        Entity::NAME,
        Entity::PROVIDER,
        Entity::CUSTOMER_ID,
        Entity::ENTITY_TYPE,
        EsRepository::CUSTOMER_EMAIL,
        EsRepository::CUSTOMER_NAME,
        EsRepository::CUSTOMER_CONTACT,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
    ];
}
