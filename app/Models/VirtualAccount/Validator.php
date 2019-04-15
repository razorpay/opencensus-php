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

    /**
     * @param array $receivers
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateReceiversForBanking(array $receivers)
    {
        /** @var Entity $virtualAccount */
        $virtualAccount = $this->entity;

        if ($virtualAccount->isBalanceTypeBanking() === false)
        {
            return;
        }

        // Must only have types as [bank_account] for banking balance case.
        if ((count($receivers[Entity::TYPES]) !== 1) or
            ($receivers[Entity::TYPES][0] !== Receiver::BANK_ACCOUNT))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Receiver of type bank_account must only exist',
                Entity::RECEIVERS,
                compact('receivers'));
        }

        // Must no other virtual account exists against this banking balance
        $exists = app('repo')->virtual_account->existsByBalanceId($virtualAccount->getBalanceId());

        if ($exists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Only one virtual account per banking balance must exist',
                Entity::RECEIVERS,
                compact('receivers'));
        }
    }

    public function validateOfPrimaryBalance()
    {
        if ($this->entity->isBalanceTypePrimary() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Operation is not allowed for this specific virtual account',
                null,
                [
                    Entity::ID => $this->entity->getId(),
                ]);
        }
    }
}
