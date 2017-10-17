<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'sometimes|filled|string|max:40',
        Entity::DESCRIPTOR      => 'sometimes|nullable|alpha_num|custom',
        Entity::AMOUNT_EXPECTED => 'sometimes|filled|integer|min:0',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::CUSTOMER_ID     => 'sometimes|filled|public_id|size:19',
        Entity::RECEIVER_TYPES  => 'sometimes|filled|array',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::NAME            => 'sometimes|filled|string|max:40',
        Entity::STATUS          => 'sometimes|in:closed',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $createValidators = [
        Entity::RECEIVER_TYPES
    ];

    protected function validateDescriptor($attribute, $descriptor)
    {
        $merchant = $this->entity->merchant;

        $descriptorLength = strlen($descriptor);

        $handleLength = strlen($merchant->getHandle());

        $rootLength = Receiver::ROOT_LENGTH;

        if (($descriptorLength + $handleLength + $rootLength) > Receiver::ACCOUNT_NUMBER_LENGTH)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
                'descriptor',
                [
                    'descriptor' => $descriptor,
                ]);
        }
    }

    protected function validateReceiverTypes(array $input)
    {
        if ((isset($input[Entity::RECEIVER_TYPES]) === true) and
            (Receiver::areTypesValid($input[Entity::RECEIVER_TYPES]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type',
                $input);
        }
    }
}
