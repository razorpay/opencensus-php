<?php

namespace RZP\Models\Transfer;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsConstants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ToType::ACCOUNT              => 'sometimes|string|size:18',
        ToType::CUSTOMER             => 'sometimes|string|size:19',
        Entity::ACCOUNT_CODE         => 'sometimes|custom',
        ToType::BALANCE              => 'string|in:fee_credit,refund_credit,reserve_balance|custom',
        Entity::AMOUNT               => 'required|integer|min:100',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::LINKED_ACCOUNT_NOTES => 'sometimes|array',
        Entity::ON_HOLD              => 'required_with:on_hold_until|boolean',
        Entity::ON_HOLD_UNTIL        => 'sometimes|nullable|epoch',
        Entity::STATUS               => 'sometimes|string',
        Entity::ORIGIN               => 'filled',
    ];

    protected static $accountCodeRules = [
        Entity::ACCOUNT_CODE        => 'string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
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

    protected static $debugRouteRules = [
        'option'        => 'required|string',
        'data'          => 'required|array',
    ];

    protected function validateBalance(string $key, string $value, array $data) {

        // Only on type of recipient is allowed, confirm that others aren't there
        foreach (ToType::$allowedTypes as $type)
        {
            // Skip the one in the request
            if ($key === $type)
            {
                continue;
            }

            if (isset($data[$type]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The '.$key.' field is required when '.$type.' is not present.');
            }
        }
    }

    public static function validateStatus($status)
    {
        if (Status::isStatusValid($status) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }

    public static function validateOrigin($origin)
    {
        if (Origin::isOriginValid($origin) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid origin: ' . $origin);
        }
    }

    public function validateTransfers(Payment\Entity $payment, array $transfers, $allTransfers)
    {
        // allTransfers contains order and payment transfers
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

        $this->validateTransferAmount($payment, $transferSum, $allTransfers);
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

    protected function validateTransferAmount(Payment\Entity $payment, int $transferSum, $allTransfers)
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

        $totalTransferUnprocessedAmount = 0;

        foreach ($allTransfers as $transfer)
        {
            if (($transfer->getStatus() === Status::CREATED) or
                ($transfer->getStatus() === Status::PENDING) or
                ($transfer->getStatus() === Status::FAILED and $transfer->getAttempts() < Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS))
            {
                $totalTransferUnprocessedAmount += $transfer->getAmount();
            }
        }

        if ($transferSum > ($payment->getAmountUntransferred() - $totalTransferUnprocessedAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_UNTRANSFERRED,
                Entity::AMOUNT,
                [
                    'sum'           => $transferSum,
                    'untransferred' => $payment->getAmountUntransferred(),
                    'unprocessed'   => $totalTransferUnprocessedAmount,
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

    public function validateMerchantBalanceForTransfer(Merchant\Entity $merchant, Balance\Entity $merchantBalance)
    {
        $debit = $this->entity->transaction->getDebit();

        try
        {
            $negativeBalanceEnabled = (new BalanceConfig\Core)->isNegativeBalanceEnabledForTxnAndMerchant(
                                                                Transaction\Type::TRANSFER);

            (new Merchant\Balance\Core)->checkMerchantBalance($merchant, -1 * $debit,
                                                        Transaction\Type::TRANSFER,
                                                                $negativeBalanceEnabled,
                                                     Balance\Type::PRIMARY);
        }
        catch (\Exception $e)
        {
            if ($e->getCode() !== ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED)
            {
                $errorCode = ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE;
            }
            else
            {
                $errorCode = $e->getCode();
            }

            throw new Exception\BadRequestException(
                $errorCode,
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

        //
        // Direct transfer limit can be increased for specific MIDs via Redis
        // config to allow them to bulk transfer their payment transfer volumes.
        // Slack: https://razorpay.slack.com/archives/C03RY88T214/p1678094313684049
        //
        $maxAmountConfig = (new Admin\Service())->getConfigKey(['key' => Admin\ConfigKey::DIRECT_TRANSFER_LIMITS]);

        if (isset($maxAmountConfig[$merchant->getId()]) === true)
        {
            $maxAmount = $maxAmountConfig[$merchant->getId()];
        }

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

    public function validateLinkedAccountNotes($laNotes, $laNotesKeys)
    {
        if (count($laNotes) !== count($laNotesKeys))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING,
                Entity::NOTES,
                array_intersect(array_keys($laNotes), $laNotesKeys));
        }
    }

    public function validateTransferForOrder(array $transfers, int $orderAmount)
    {

        // Array of recipient ID types sent in the
        // transfer request. (possible: customer, account)
        $keys = [];

        $transferCount = $transferSum = 0;

        foreach ($transfers as $transfer)
        {
            $this->validateInput('create', $transfer);

            $transferNotes = $transfers[Entity::NOTES] ?? [];

            $laNotesKeys = $transfers[Entity::LINKED_ACCOUNT_NOTES] ?? [];

            if ((empty($laNotesKeys) === false) and (is_array($laNotesKeys) === true))
            {
                $laNotes = array_only($transferNotes, $laNotesKeys);

                $this->validateLinkedAccountNotes($laNotes, $laNotesKeys);
            }

            $transferSum += (int) $transfer[Entity::AMOUNT];

            $keySet = false;

            $transferCount++;

            // Fail if at least one of the values in
            // ToType::$allowedTypes is not set for a transfer
            if (isset($transfer[ToType::ACCOUNT]) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ORDER_TRANSFER_ENTITIES_NOT_SET);
            }

            $keys[] = ToType::ACCOUNT;
        }

        $this->validateTransferEntities($keys, $transferCount);

        if ($transferSum > $orderAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_AMOUNT_GREATER_THAN_ORDER_AMOUNT);
        }
    }

    public function validateBalanceTransferChecks(array $transfers, string $merchantId, int $orderAmount)
    {
        if (sizeof($transfers) > 1)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_NODES_MORE_THAN_ONE_FOR_BALANCE_TRANSFER);
        }
        if ($transfers[0]['account'] != 'acc_' . $merchantId)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_ID_INVALID_FOR_BALANCE_TRANSFER);
        }
        if ($transfers[0]['amount'] != $orderAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRANSFER_AMOUNT_FOR_BALANCE_TRANSFER);
        }
    }

    public function validateAccountCode($key, $value)
    {
        $this->validateInput('account_code', [$key => $value]);
    }

    public function validateToType(array $transferInput)
    {
        $countKeys = 0;

        $countKeys += (int) array_key_exists(ToType::ACCOUNT, $transferInput);

        $countKeys += (int) array_key_exists(ToType::CUSTOMER, $transferInput);

        $countKeys += (int) array_key_exists(Entity::ACCOUNT_CODE, $transferInput);

        if ($countKeys !== 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exactly one of account, account_code & customer to be passed.'
            );
        }
    }

    public function validateMerchantActivationStatusAndBankVerificationStatus(Merchant\Detail\Entity $merchantDetail)
    {
        if($merchantDetail->merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_SUSPENDED
            );
        }
        if($this->isLiveMode() === false)
        {
            return;
        }
        if ($merchantDetail->merchant->isFeatureEnabledOnParentMerchant(
                FeatureConstants::ROUTE_LA_PENNY_TESTING) === false)
        {
            return;
        }
        $bankDetailsVerificationStatus = $merchantDetail->getBankDetailsVerificationStatus();

        if ($bankDetailsVerificationStatus === BvsConstants::INCORRECT_DETAILS or
            $bankDetailsVerificationStatus === BvsConstants::NOT_MATCHED or
            $bankDetailsVerificationStatus === BvsConstants::FAILED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INCORRECT_BANK_ACCOUNT_DETAILS
            );
        }
        else if($bankDetailsVerificationStatus !== BvsConstants::VERIFIED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BANK_DETAILS_VERIFICATION_PENDING
            );
        }
    }
}
