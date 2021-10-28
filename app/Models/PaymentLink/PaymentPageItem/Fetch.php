<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Item;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID               => 'filled|alpha_num|size:14',
            Item\Entity::NAME        => 'filled|string|min:2|max:40',
            Entity::ITEM_DELETED_AT  => 'nullable|int',
            Entity::MERCHANT_ID      => 'filled|alpha_num|size:14',
            Entity::PAYMENT_LINK_ID  => 'filled|alpha_num|size:14',
            Item\Entity::DESCRIPTION => 'filled|string|min:2|max:255',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [

        ],
        AuthType::PROXY_AUTH => [
            Entity::ID,
            Item\Entity::NAME,
            Item\Entity::DESCRIPTION,
            Entity::ITEM_DELETED_AT,
            Entity::PAYMENT_LINK_ID,
        ],

        AuthType::PRIVILEGE_AUTH => [

        ],
    ];

    const SIGNED_IDS = [
    ];

    const ES_FIELDS = [
        Entity::ID,
        Item\Entity::NAME,
        Item\Entity::DESCRIPTION,
        Entity::ITEM_DELETED_AT,
        Entity::PAYMENT_LINK_ID,
    ];

    const COMMON_FIELDS = [

    ];
}
