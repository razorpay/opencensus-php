<?php

namespace RZP\Models\Transfer;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ToType::ACCOUNT              => 'required_without:customer|string|size:18',
        ToType::CUSTOMER             => 'required_without:account|string|size:19',
        Entity::AMOUNT               => 'required|integer|min:100',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::LINKED_ACCOUNT_NOTES => 'sometimes|array',
        Entity::ON_HOLD              => 'required_with:on_hold_until|boolean',
        Entity::ON_HOLD_UNTIL        => 'sometimes|nullable|epoch',
    ];

    protected static $createValidators = [
        'hold_parameters'
    ];

    protected static $editRules = [
        Entity::ON_HOLD       => 'required|boolean',
        Entity::ON_HOLD_UNTIL => 'sometimes|integer',
    ];

    protected static $editValidators = [
        'hold_parameters'
    ];

    public function validateTransfers(Payment\Entity $payment, array $transfers)
    {
        // Array of recipient ID types sent in the
        // transfer request. (possible: customer, account)
        $keys = [];

        $transferCount = $transferSum = 0;

        foreach ($transfers as $transfer)
        {
            $this->validateInput('create', $transfer);

            $transferSum += (int) $transfer[Entity::AMOUNT];

            $this->validateTransferCurrency($payment, $transfer[Entity::CURRENCY]);

            $keySet = false;

            $transferCount++;

            foreach (ToType::$allowedTypes as $type)
            {
                if (isset($transfer[$type]) === true)
                {
                    $keySet = true;
                    $keys[] = $type;
                }
            }

            // Fail if at least one of the values in
            // ToType::$allowedTypes is not set for a transfer
            if ($keySet === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_ENTITIES_NOT_SET);
            }
        }

        $this->validateTransferEntities($keys, $transferCount);

        $this->validateTransferAmount($payment, $transferSum);
    }

    protected function validateTransferEntities(array $keys, int $transferCount)
    {
        $uniqueKeys = array_unique($keys);

        // Allow only one transfer to customer per request
        if ((count($uniqueKeys) === 1) and
            ($uniqueKeys[0] === ToType::CUSTOMER) and
            ($transferCount !== 1))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_MORE_THAN_ONE_CUSTOMER);
        }

        // Allow a transfer to only either customer or account per
        // request, not both.
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
     *
     * @throws Exception\BadRequestException
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
                    'payment_currency'  => $payment->getCurrency(),
                    'payment_id'        => $payment->getId(),
                ]);
        }
    }

    protected function validateTransferAmount(Payment\Entity $payment, int $transferSum)
    {
        //
        // For now -
        // 1. Sum of transfers cant be greater than the capture amount
        // 2. Sum of transfers should be greater than merchant balance
        //
        if ($transferSum > $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($transferSum > $payment->getAmountUntransferred())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_UNTRANSFERRED,
                Entity::AMOUNT,
                [
                    'sum'           => $transferSum,
                    'untransferred' => $payment->getAmountUntransferred()
                ]);
        }
    }

    public function validateHoldParameters(array $input)
    {
        if (isset($input[Entity::ON_HOLD]) === false)
        {
            return;
        }

        if (isset($input[Entity::ON_HOLD_UNTIL]) === true)
        {
            if ($input[Entity::ON_HOLD] === '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold field must be set to 1, if on_hold_until is sent');
            }

            $now = Carbon::now()->getTimestamp();

            if ($input[Entity::ON_HOLD_UNTIL] < $now)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold_until timestamp cannot be less than the current timestamp');
            }
        }
    }

    public function validateMerchantBalanceForTransfer(Merchant\Balance\Entity $merchantBalance)
    {
        $debit = $this->entity->transaction->getDebit();

        if ($debit > $merchantBalance->getBalance())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                Entity::AMOUNT,
                [
                    'debit_amount' => $debit,
                    'balance'      => $merchantBalance->getBalance()
                ]);
        }
    }

    public function validateTransferMaxAmount(int $amount, Merchant\Entity $merchant)
    {
        $maxAmount = $merchant->getMaxPaymentAmount();

        if ($amount > $maxAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds the maximum amount allowed',
                Entity::AMOUNT,
                [
                    'max_payment_amount' => $maxAmount
                ]
            );
        }
    }
}
