<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Transaction;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Transaction\Statement
 */
class Fetch extends Transaction\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID      => 'sometimes|unsigned_id',
            Entity::CONTACT_ID      => 'sometimes|public_id|size:19',
            Entity::PAYOUT_ID       => 'sometimes|public_id|size:19',
            Entity::CONTACT_NAME    => 'sometimes|string|max:255',
            Entity::CONTACT_PHONE   => 'sometimes|contact_syntax',
            Entity::CONTACT_EMAIL   => 'sometimes|email',
            Entity::FUND_ACCOUNT_ID => 'sometimes|public_id|size:17',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::BALANCE_ID,
            Entity::CONTACT_ID,
            Entity::PAYOUT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE,
            Entity::CONTACT_EMAIL,
            Entity::FUND_ACCOUNT_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::PAYOUT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];
}
