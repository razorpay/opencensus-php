<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::DESCRIPTOR      => 'sometimes|nullable|alpha_num',
        Entity::AMOUNT_EXPECTED => 'filled|integer|min:0',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::CUSTOMER_ID     => 'filled|public_id|size:19',
        Entity::RECEIVERS       => 'required|array',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::STATUS          => 'sometimes|in:closed',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
    ];

    protected static $createValidators = [
        Entity::RECEIVERS
    ];

    protected static $bankAccountReceiverOptionRules = [
        Entity::NUMERIC    => 'sometimes|boolean',
        Entity::DESCRIPTOR => 'sometimes|alpha_num|max:10',
    ];

    public function validateDescriptor($descriptor)
    {
        if ($descriptor === null)
        {
            return;
        }

        $handle = $this->entity->merchant->getHandle();

        $descriptorLength = strlen($descriptor);

        $handleLength = strlen($handle);

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

    protected function validateReceivers(array $input)
    {
        if (isset($input[Entity::RECEIVERS][Entity::TYPES]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'requests.types is required.');
        }

        if (Receiver::areTypesValid($input[Entity::RECEIVERS][Entity::TYPES]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type',
                $input);
        }
    }
}
