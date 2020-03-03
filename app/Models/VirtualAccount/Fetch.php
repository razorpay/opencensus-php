<?php

namespace RZP\Models\VirtualAccount;

use RZP\Error\ErrorCode;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Exception\BadRequestValidationFailureException;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::STATUS      => 'sometimes|in:active,closed,paid',
            Entity::CUSTOMER_ID => 'sometimes|string|min:14|max:19',
            Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
            Entity::NOTES       => 'sometimes|notes_fetch',
            Entity::BALANCE_ID  => 'sometimes|unsigned_id',
        ],
        AuthType::PROXY_AUTH => [
            Entity::RECEIVER_TYPE => 'sometimes|string|custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::STATUS,
            Entity::NOTES,
            Entity::CUSTOMER_ID,
            Entity::BALANCE_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::RECEIVER_TYPE,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
    ];

    const ES_FIELDS = [
        Entity::NOTES,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
    ];

    public function validateReceiverType($attribute, $values)
    {
        $values = explode(',', $values);

        if (Receiver::areTypesValid($values) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type',
                $values
            );
        }
    }
}
