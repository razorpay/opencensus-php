<?php

namespace RZP\Models\Payout;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;

class Validator extends Base\Validator
{
    //
    // This is required for build. Currently, build does not
    // accept ruleName as a parameter. Hence, this list needs
    // to contain the master attributes. We run a different
    // validation for the actual operation.
    //
    protected static $createRules = [
        Entity::DESTINATION     => 'required|public_id',
        Entity::PURPOSE         => 'sometimes|string',
        Entity::AMOUNT          => 'sometimes|integer',
        Entity::CURRENCY        => 'sometimes|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CUSTOMER_ID     => 'sometimes|public_id',
        Entity::DESTINATION     => 'sometimes|public_id',
        Entity::TYPE            => 'sometimes|string',
        Entity::BALANCE_ID      => 'sometimes|string|size:14',
        Entity::FUND_ACCOUNT_ID => 'sometimes|public_id',
        Entity::MODE            => 'sometimes|string',
    ];

    protected static $fundAccountPayoutRules = [
        Entity::PURPOSE         => 'sometimes|filled|string|max:30|in:refund',
        Entity::AMOUNT          => 'required|integer|min:100|max:500000000',
        Entity::CURRENCY        => 'required|size:3|in:INR',
        Entity::NOTES           => 'sometimes|notes',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID => 'required|public_id',
        Entity::MODE            => 'sometimes|string|custom',
    ];

    protected static $customerWalletPayoutRules = [
        Entity::PURPOSE         => 'sometimes|filled|string|max:30|in:refund',
        Entity::AMOUNT          => 'required|integer|min:100|max:500000000',
        Entity::CURRENCY        => 'required|size:3|in:INR',
        Entity::NOTES           => 'sometimes|notes',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID => 'required|public_id',
    ];

    protected static $merchantPayoutRules = [
        Entity::PURPOSE         => 'required|string|max:30|in:settlement',
        Entity::METHOD          => 'sometimes|string',
        Entity::AMOUNT          => 'required|integer|max:800000000',
        Entity::CURRENCY        => 'required|size:3',
        Entity::TYPE            => 'required|string|max:30|in:default,on_demand',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
    ];

    protected static $merchantRules = [
        Entity::MERCHANT_ID    => 'required|string|size:14',
        Entity::AMOUNT         => 'sometimes|integer|max:800000000',
        Entity::MIN_AMOUNT     => 'sometimes|integer|min:100',
        Entity::MODULO         => 'sometimes|integer|min:100',
        Entity::BUFFER_AMOUNT  => 'sometimes|integer|min:10000000'
    ];

    protected static $merchantPayoutOnDemandRules = [
        Entity::AMOUNT   => 'required|integer|min:100',
        Entity::CURRENCY => 'required|size:3',
    ];

    protected static $payoutRetryRules = [
        'ids'    => 'required|array',
        'ids.*'  => 'required|public_id|size:19'
    ];

    protected static $fundAccountPayoutValidators = [
        Entity::MODE,
    ];

    protected function validateMethod($attribute, $method)
    {
        Method::validateMethod($method);
    }

    protected function validateMode($input)
    {
        if (isset($input[Entity::MODE]) === false)
        {
            return;
        }

        $mode = $input[Entity::MODE];

        Mode::validateMode($mode);

        $amount = $input[Entity::AMOUNT];

        $minRtgsAmount = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount = NodalAccount::MAX_IMPS_AMOUNT * 100;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'            => $amount,
                    'mode'              => $mode,
                    'min_rtgs_amount'   => $minRtgsAmount,
                    'max_imps_amount'   => $maxImpsAmount,
                ]);
        }

        $accountType = $this->entity->fundAccount->getAccountType();

        if ($accountType !== FundAccount\Type::BANK_ACCOUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_TYPE_PASSED_FOR_MODE,
                null,
                [
                    'amount'            => $amount,
                    'mode'              => $mode,
                    'account_type'      => $accountType,
                    'fund_account_id'   => $this->entity->fundAccount->getId(),
                ]);
        }
    }

    public function validatePayoutAmount($input, $payment)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $payoutAmount = $input[Entity::AMOUNT];

        $payoutAmountPending = $payment->getAmount() - $payment->getAmountPaidout();

        if ($payoutAmountPending === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_PAIDOUT);
        }

        if ($payoutAmount > $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($payoutAmount > $payoutAmountPending)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING);
        }
    }

    /**
     * Validate that a payout can be created on a particular payment
     *
     * @param array          $input
     * @param Payment\Entity $payment
     */
    public function validatePaymentForPayout(array $input, Payment\Entity $payment)
    {
        $this->validateBankPayoutsFromCardPayments($input, $payment);
    }

    protected function validateBankPayoutsFromCardPayments(array $input, Payment\Entity $payment)
    {
        //
        // If method is not sent in input, skip the
        // following validation and allow the call to
        // fail during Payout build
        //
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        // Only validating for card payments
        if ($payment->isCard() === false)
        {
            return;
        }

        $card = $payment->card;

        if ($card->getType() === Card\Type::CREDIT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_FUND_TRANSFER_ON_CREDIT_CARD_PAYMENT);
        }
    }
}
