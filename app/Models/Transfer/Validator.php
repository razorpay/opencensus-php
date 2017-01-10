<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $transferRules = [
        ToType::ACCOUNT        => 'required_without:customer|string|size:18',
        ToType::CUSTOMER       => 'required_without:account|string|size:19',
        Entity::AMOUNT         => 'required|integer',
        Entity::CURRENCY       => 'required|size:3',
        Entity::ON_HOLD        => 'required_with:hold_until|boolean',
        Entity::HOLD_UNTIL     => 'sometimes|integer',
    ];

    protected static $editRules = [
        Entity::ON_HOLD        => 'required|boolean',
        Entity::HOLD_UNTIL     => 'sometimes|integer',
    ];

    protected static $editValidators = [
        'hold_parameters'
    ];

    public function validateTransfers(
        Payment\Entity $payment,
        Merchant\Balance\Entity $merchantBalance,
        array $transfers)
    {
        $keys = [];

        $transferCount = 0;

        foreach ($transfers as $transfer)
        {
            $this->validateTransferCurrency($payment, $transfer['currency']);

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

    /**
     * Fail if the requested transfer currency is not same as payment currency
     *
     * @param  Payment\Entity $payment
     * @param  string         $currency
     */
    protected function validateTransferCurrency(Payment\Entity $payment, string $currency)
    {
        if ($currency !== $payment->getCurrency())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_CURRENCY_MISMATCH,
                Payment\Entity::CURRENCY,
                [
                    'transfer_currency' => $currency,
                    'payment_currency' => $payment->getCurrency(),
                    'payment_id'       => $payment->getId(),
                ]);
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

    public function validateHoldParameters(array $input)
    {
        if ((isset($input['on_hold']) === false) and
            (isset($input['hold_until']) === false))
        {
            return;
        }

        if (isset($input['hold_until']) === true)
        {
            if ($input['on_hold'] === '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold field must be set to 1, if hold_until is sent');
            }

            $now = Carbon::now('Asia/Kolkata');

            if ($input['hold_until'] < $now->timestamp)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The hold_until timestamp cannot be less than the current timestamp');
            }
        }
    }
}
