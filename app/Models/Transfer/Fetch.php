<?php

namespace RZP\Models\Transfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Exception\BadRequestValidationFailureException;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::RECIPIENT                   => 'sometimes|string|max:20',
            Entity::RECIPIENT_SETTLEMENT_ID     => 'filled|string|public_id',
            self::EXPAND_EACH                   => 'filled|string|in:recipient_settlement,transaction.settlement',
            Entity::TRANSACTION_ID              => 'sometimes|alpha_num|size:14',
            Entity::MERCHANT_ID                 => 'sometimes|alpha_num|size:14',
            Entity::SOURCE                      => 'sometimes|string|min:14',
            Entity::STATUS                      => 'sometimes|array',
            Entity::SETTLEMENT_STATUS           => 'sometimes|array',
            Entity::ERROR_CODE                  => 'sometimes|string',
            Entity::ACCOUNT_CODE                => 'sometimes|string|min:3|max:20',
            Entity::ACCOUNT_CODE_USED           => 'sometimes|boolean',
            Constant::EXCLUDED_LINKED_ACCOUNTS  => 'sometimes|array',
            Constant::INCLUDED_LINKED_ACCOUNTS  => 'sometimes|array',
        ],
        AuthType::PROXY_AUTH => [
            Entity::STATUS                      => 'sometimes|custom',
            Entity::SETTLEMENT_STATUS           => 'sometimes|string',
            Constant::EXCLUDED_LINKED_ACCOUNTS  => 'sometimes|array',
            Constant::INCLUDED_LINKED_ACCOUNTS  => 'sometimes|array',
            Entity::SOURCE                      => 'sometimes|string|min:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::TRANSACTION_ID,
            Entity::MERCHANT_ID,
            Entity::SOURCE,
            Entity::ACCOUNT_CODE,
            Entity::ACCOUNT_CODE_USED,
            Entity::STATUS,
            Entity::SETTLEMENT_STATUS,
            Entity::ERROR_CODE,
        ],

        AuthType::PRIVATE_AUTH => [
            Entity::RECIPIENT,
            Entity::RECIPIENT_SETTLEMENT_ID,
            self::EXPAND_EACH,
            Entity::STATUS,
            Entity::SETTLEMENT_STATUS,
            Constant::EXCLUDED_LINKED_ACCOUNTS,
            Constant::INCLUDED_LINKED_ACCOUNTS,
            Entity::SOURCE,
        ],
    ];

    protected function validateStatus($key, $value)
    {
        if ((is_array($value) === false) and
            (is_string($value) === false))
        {
            throw new BadRequestValidationFailureException('Invalid status attribute');
        }
    }
}
