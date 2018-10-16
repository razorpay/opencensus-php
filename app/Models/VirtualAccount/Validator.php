<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                            => 'filled|string|max:40',
        Entity::AMOUNT_EXPECTED                 => 'filled|integer|min:0',
        Entity::DESCRIPTION                     => 'sometimes|nullable|string|max:2048',
        Entity::CUSTOMER_ID                     => 'filled|public_id|size:19',
        Entity::ORDER_ID                        => 'filled|public_id|size:20',
        Entity::RECEIVERS                       => 'bail|required|array|custom',
        Entity::RECEIVERS . '.' . Entity::TYPES => 'present|array',
        Entity::NOTES                           => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::STATUS          => 'sometimes|in:closed',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $bankAccountReceiverOptionRules = [
        Entity::NUMERIC    => 'sometimes|boolean',
        Entity::DESCRIPTOR => 'sometimes|alpha_num|max:10',
    ];

    protected function validateReceivers(string $key, array $value, array $data)
    {
        if ((isset($value[Entity::TYPES]) === true) and
            (is_array($value[Entity::TYPES]) === true) and
            (Receiver::areTypesValid($value[Entity::TYPES]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type',
                $data);
        }
    }
}
