<?php

namespace RZP\Models\VirtualAccount;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'sometimes|string|max:40',
        Entity::DESCRIPTOR      => 'sometimes|alpha_num|max:10',
        Entity::AMOUNT_EXPECTED => 'sometimes|integer|min:0',
        Entity::CUSTOMER_ID     => 'sometimes|public_id|size:19',
        Entity::RECEIVER_TYPE   => 'sometimes|array'
    ];

    protected static $editRules = [
        Entity::STATUS          => 'required|string|in:closed',
    ];

    protected static $createValidators = [
        Entity::RECEIVER_TYPE
    ];

    protected function validateReceiverType(array $input)
    {
        $receiverTypes = $input[Entity::RECEIVER_TYPE];

        foreach ($receiverTypes as $type)
        {
            if (Receiver::isTypeValid($type) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_RECEIVER_TYPE,
                    'receiver_type',
                    [
                        'receiver_type' => $type,
                    ]);
            }
        }
    }
}
