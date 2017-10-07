<?php

namespace RZP\Models\Payment;

use RZP\Models\Card;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends \RZP\Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            self::EXPAND_EACH          => 'string|in:card,',
            Entity::EMAIL              => 'sometimes|email',
            Entity::ORDER_ID           => 'sometimes|string|size:20',
            Entity::TRANSFERRED        => 'sometimes|boolean|in:0,1',
            Entity::STATUS             => 'sometimes|string',
            Entity::NOTES              => 'sometimes|notes_fetch',
            Entity::INVOICE_ID         => 'sometimes|string|max:18',
            Entity::VERIFIED           => 'sometimes|in:null,0,1,2',
            Entity::REFUND_STATUS      => 'sometimes|in:null,partial,full',
            Entity::TWO_FACTOR_AUTH    => 'sometimes|string',
            Entity::BANK               => 'sometimes',
            Entity::METHOD             => 'sometimes',
            Entity::GATEWAY            => 'sometimes',
            Entity::MERCHANT_ID        => 'sometimes|alpha_num',
            Entity::TRANSFER_ID        => 'sometimes|alpha_num|size:14',
            Entity::CARD_ID            => 'sometimes|alpha_num|size:14',
            Entity::CAPTURED           => 'sometimes|in:0,1',
            Entity::WALLET             => 'sometimes|custom',
            Card\Entity::IIN           => 'sometimes|integer|digits:6',
            Card\Entity::LAST4         => 'sometimes|string|digits:4',
            Card\Entity::INTERNATIONAL => 'sometimes|in:0,1',
            Entity::CUSTOMER_ID        => 'sometimes|alpha_num|size:14',
            Entity::TOKEN_ID           => 'sometimes|alpha_num|size:14',
            Entity::GLOBAL_TOKEN_ID    => 'sometimes|alpha_num|size:14',
            Entity::SAVE               => 'sometimes|in:0,1',
            Entity::LATE_AUTHORIZED    => 'sometimes|in:0,1',
            Entity::AMOUNT             => 'sometimes|integer',
            Entity::TERMINAL_ID        => 'sometimes|alpha_num|size:14',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::EMAIL,
            Entity::ORDER_ID,
            Entity::TRANSFERRED,
        ],
        AuthType::PROXY_AUTH => [
            Entity::STATUS,
            Entity::NOTES,
            Entity::INVOICE_ID,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::VERIFIED,
            Entity::REFUND_STATUS,
            Entity::TWO_FACTOR_AUTH,
            Entity::BANK,
            Entity::METHOD,
            Entity::GATEWAY,
            Entity::MERCHANT_ID,
            Entity::TRANSFER_ID,
            Entity::CARD_ID,
            Entity::CAPTURED,
            Entity::WALLET,
            Card\Entity::IIN,
            Card\Entity::LAST4,
            Card\Entity::INTERNATIONAL,
            Entity::CUSTOMER_ID,
            Entity::TOKEN_ID,
            Entity::GLOBAL_TOKEN_ID,
            Entity::SAVE,
            Entity::LATE_AUTHORIZED,
            Entity::AMOUNT,
            Entity::TERMINAL_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::ORDER_ID,
        Entity::INVOICE_ID,
    ];

    const ES_FIELDS = [
        Entity::NOTES,
    ];

    protected $enabled = false;
}
