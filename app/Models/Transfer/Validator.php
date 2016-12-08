<?php

namespace RZP\Models\Transfer;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TO_ID          => 'required|alpha_num|size:14',
        Entity::TO_TYPE        => 'required|string',
        Entity::SOURCE_ID      => 'required|alpha_num|size:14',
        Entity::SOURCE_TYPE    => 'required|string',
        Entity::AMOUNT         => 'required|integer',
    ];

    public function validateTransfers(Payment\Entity $payment, Merchant\Balance\Entity $merchantBalance, array $transfers)
    {
        $keys = [];

        $transferCount = 0;

        foreach ($transfers as $transfer)
        {
            $keySet = false;

            ++$transferCount;

            foreach (ToType::$allowedTypes as $type)
            {
                if (isset($transfer[$type]) === true)
                {
                    $keySet = true;
                    $keys[] = $type;
                }
            }

            if ($keySet === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_ENTITIES_NOT_SET);
            }
        }

        $this->validateTransferEntities($keys, $transferCount);

        $this->validateTransferAmount($payment, $merchantBalance, $transfers);
    }

    protected function validateTransferEntities(array $keys, int $transferCount)
    {
        $uniqueKeys = array_unique($keys);

        if ((count($uniqueKeys) === 1) and
            ($uniqueKeys[0] === ToType::CUSTOMER) and
            ($transferCount !== 1))
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_MORE_THAN_ONE_CUSTOMER);
        }

        if (count($uniqueKeys) > 1)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN);
        }
    }

    protected function validateTransferAmount(Payment\Entity $payment, Merchant\Balance\Entity $merchantBalance, array $transfers)
    {
        // For now -
        // 1. Sum of transfers cant be greater than the capture amount
        // 2. Sum of transfers should be greater than merchant balance

        $transferSum = 0;

        foreach ($transfers as $transfer)
        {
            $transferSum += $transfer['amount'];
        }

        if ($transferSum > $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($transferSum > $payment->getAmountUntransferred())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_UNTRANSFERRED);
        }

        if ($transferSum > $merchantBalance->getBalance())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE);
        }
    }
}
