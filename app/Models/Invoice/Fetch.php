<?php

namespace RZP\Models\Invoice;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TYPE              => 'sometimes|string|custom',
            Entity::PAYMENT_ID        => 'sometimes|string|min:14|max:18',
            Entity::RECEIPT           => 'sometimes|string|min:1|max:40',
            Entity::CUSTOMER_ID       => 'sometimes|string|min:14|max:19',
            Entity::BATCH_ID          => 'sometimes|string|min:14|max:20',
            Entity::USER_ID           => 'sometimes|alpha_num',
            Entity::STATUS            => 'sometimes|string',
            Entity::TYPES             => 'sometimes|array|min:1|max:2|custom',
            Entity::CUSTOMER_NAME     => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:255',
            Entity::CUSTOMER_CONTACT  => 'sometimes|contact_syntax',
            Entity::CUSTOMER_EMAIL    => 'sometimes|email',
            Entity::NOTES             => 'sometimes|notes_fetch',
            Entity::SUBSCRIPTION_ID   => 'sometimes|string|min:14|max:18',
            EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
            Entity::MERCHANT_ID       => 'sometimes|alpha_num',
            Entity::ORDER_ID          => 'sometimes|string|max:20',
            self::EXPAND_EACH         => 'string|in:payments,payments.card,user',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::TYPE,
            Entity::PAYMENT_ID,
            Entity::RECEIPT,
            Entity::CUSTOMER_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::BATCH_ID,
            Entity::USER_ID,
            Entity::STATUS,
            Entity::TYPES,
            Entity::CUSTOMER_NAME,
            Entity::CUSTOMER_CONTACT,
            Entity::CUSTOMER_EMAIL ,
            Entity::NOTES,
            Entity::SUBSCRIPTION_ID,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
            self::EXPAND_EACH,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::ORDER_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
        Entity::CUSTOMER_ID,
        Entity::ORDER_ID,
        Entity::SUBSCRIPTION_ID,
        Entity::BATCH_ID,
    ];

    const ES_FIELDS = [
        EsRepository::QUERY,
        Entity::NOTES,
        Entity::TERMS,
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
    ];

    const COMMON_FIELDS = [
        Entity::STATUS,
        Entity::TYPE,
        Entity::TYPES,
        Entity::MERCHANT_ID,
        Entity::USER_ID,
    ];

    protected $enabled = true;

    // ---------------------- Custom validation methods --------------

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    protected function validateTypes($attribute, $value)
    {
        foreach ($value as $type)
        {
            Type::checkType($type);
        }
    }
}
