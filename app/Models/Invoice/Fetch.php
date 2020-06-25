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
            Entity::TYPES             => 'sometimes|sequential_array|min:1|max:2|custom',
            Entity::CUSTOMER_NAME     => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:255',
            Entity::CUSTOMER_CONTACT  => 'sometimes|contact_syntax',
            Entity::CUSTOMER_EMAIL    => 'sometimes|email',
            Entity::NOTES             => 'sometimes|notes_fetch',
            Entity::SUBSCRIPTION_ID   => 'sometimes|string|min:14|max:18',
            EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
            Entity::MERCHANT_ID       => 'sometimes|alpha_num',
            Entity::ORDER_ID          => 'sometimes|string|max:20',
            Entity::ENTITY_TYPE       => 'sometimes|string|nullable',
            Entity::STATUSES          => 'sometimes|sequential_array|min:1|max:6|custom',
            Entity::INTERNATIONAL     => 'filled|boolean',
            Entity::SUBSCRIPTIONS     => 'filled|boolean',
            self::EXPAND_EACH         => 'filled|string|in:payments,payments.card,user,reminder_status',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::TYPE,
            Entity::PAYMENT_ID,
            Entity::RECEIPT,
            Entity::CUSTOMER_ID,
            Entity::ENTITY_TYPE,
            Entity::INTERNATIONAL,
            Entity::SUBSCRIPTION_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::BATCH_ID,
            Entity::USER_ID,
            Entity::STATUS,
            Entity::TYPES,
            Entity::STATUSES,
            Entity::CUSTOMER_NAME,
            Entity::CUSTOMER_CONTACT,
            Entity::CUSTOMER_EMAIL,
            Entity::NOTES,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
            Entity::SUBSCRIPTIONS,
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
        EsRepository::SEARCH_HITS,
        Entity::NOTES,
        Entity::TERMS,
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
    ];

    const COMMON_FIELDS = [
        Entity::SUBSCRIPTIONS,
        Entity::STATUS,
        Entity::STATUSES,
        Entity::INTERNATIONAL,
        Entity::TYPE,
        Entity::TYPES,
        Entity::MERCHANT_ID,
        Entity::USER_ID,
        Entity::ENTITY_TYPE,
    ];

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

    protected function validateStatuses($attribute, $value)
    {
        foreach ($value as $status)
        {
            Status::checkStatus($status);
        }
    }
}
