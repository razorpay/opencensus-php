<?php

namespace RZP\Models\VirtualAccount;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'sometimes|filled|string|max:40',
        Entity::DESCRIPTOR      => 'sometimes|filled|alpha_num|between:5,10',
        Entity::AMOUNT_EXPECTED => 'sometimes|filled|integer|min:0',
        Entity::CUSTOMER_ID     => 'sometimes|filled|public_id|size:19',
        Entity::RECEIVER_TYPES  => 'sometimes|filled|array',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::STATUS          => 'required|string|in:closed',
    ];

    protected static $createValidators = [
        Entity::RECEIVER_TYPES
    ];

    protected function validateReceiverTypes(array $input)
    {
        $receiverTypes = $input[Entity::RECEIVER_TYPES];

        if (Receiver::areTypesValid($receiverTypes) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type');
        }
    }
}
