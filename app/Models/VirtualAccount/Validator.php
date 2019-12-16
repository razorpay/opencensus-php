<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Base;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    // close by while creating a va should be atleast 15 mins ahead of current time
    const MIN_CLOSE_BY_DIFF = 900;

    protected static $createRules = [
        Entity::NAME                            => 'filled|string|max:40',
        Entity::AMOUNT_EXPECTED                 => 'filled|integer|min:0',
        Entity::DESCRIPTION                     => 'sometimes|nullable|string|max:2048',
        Entity::CUSTOMER_ID                     => 'filled|public_id|size:19',
        Entity::ORDER_ID                        => 'filled|public_id|size:20',
        Entity::RECEIVERS                       => 'bail|required|array|custom',
        Entity::RECEIVERS . '.' . Entity::TYPES => 'present|array',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::CLOSE_BY                        => 'filled|epoch|custom',
    ];

    protected static $editRules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::STATUS          => 'sometimes|in:closed',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CLOSE_BY        => 'filled|epoch|custom',
    ];

    protected static $bankAccountReceiverOptionRules = [
        Entity::NUMERIC    => 'sometimes|boolean',
        Entity::DESCRIPTOR => 'sometimes|alpha_num|max:10',
        Entity::NAME       => 'filled|string|max:40',
    ];

    protected static $vpaReceiverOptionRules = [
        Entity::DESCRIPTOR => 'filled|regex:/^[A-Za-z0-9\.\-]{3,}$/|max:30',
    ];

    protected static $createValidators = [
        Entity::RECEIVER_TYPES,
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
     * Currently only validating the the receivers.qr_code
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    protected function validateReceiverTypes(array $input)
    {
        if (isset($input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD]) === false)
        {
            // This case is already validated separately
            return;
        }

        if ((isset($input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD]) === true) and
            (isset($input[Entity::RECEIVERS][Entity::TYPES])) and
            (is_array($input[Entity::RECEIVERS][Entity::TYPES])))
        {
            // QR code should not be passed if receiver types has bank account
            if (in_array(Receiver::QR_CODE, $input[Entity::RECEIVERS][Entity::TYPES], true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                    'receiver_type',
                    $input);
            }
        }

        // All we want to make sure if receivers.qr_code.method is passes
        $method = $input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD];

        if (is_array($method) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_qr_code_method',
                $method);
        }

        $upi  = filter_var(array_get($method, Payment\Method::UPI), FILTER_VALIDATE_BOOLEAN);
        $card = filter_var(array_get($method, Payment\Method::CARD), FILTER_VALIDATE_BOOLEAN);

        $onlyUpi = (($upi === true) and ($card === false));

        // Currently no other combination is allowed
        if ($onlyUpi === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_method',
                $method);
        }

        // Order support is not provided for Only UPI QR
        if (($onlyUpi === true) and
            (isset($input[Entity::ORDER_ID]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER,
                'receiver_qr_code_method',
                $method);
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

    public function validateCloseBy(string $attribute, int $closeBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::MIN_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }
}
