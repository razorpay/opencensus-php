<?php

namespace RZP\Models\Payout;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;

class Validator extends Base\Validator
{
    const MAX_PURPOSES_ALLOWED = 100;

    //
    // This is required for build. Currently, build does not
    // accept ruleName as a parameter. Hence, this list needs
    // to contain the master attributes. We run a different
    // validation for the actual operation.
    //
    protected static $createRules = [
        Entity::PURPOSE              => 'sometimes|string',
        Entity::AMOUNT               => 'sometimes|integer',
        Entity::CURRENCY             => 'sometimes|size:3',
        Entity::NOTES                => 'sometimes|notes',
        Entity::CUSTOMER_ID          => 'sometimes|public_id',
        Entity::DESTINATION          => 'sometimes|public_id',
        Entity::TYPE                 => 'sometimes|string',
        Entity::BALANCE_ID           => 'sometimes|string|size:14',
        Entity::FUND_ACCOUNT_ID      => 'sometimes|public_id',
        Entity::MODE                 => 'sometimes|nullable|string',
        Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION            => 'sometimes|nullable|string|max:30',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    /**
     * @see Batch\Validator Need to change for payout rules if any changes are done here
     * @var array
     */
    protected static $fundAccountPayoutRules = [
        Entity::PURPOSE              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::AMOUNT               => 'required|integer|min:100|max:10000000000',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::BALANCE_ID           => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID      => 'required|public_id',
        Entity::MODE                 => 'sometimes|nullable|string',
        Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $customerWalletPayoutRules = [
        Entity::PURPOSE         => 'sometimes|filled|string|max:30|in:refund',
        Entity::AMOUNT          => 'required|integer|min:100|max:500000000',
        Entity::CURRENCY        => 'required|size:3|in:INR',
        Entity::NOTES           => 'sometimes|notes',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID => 'required|public_id',
        Entity::REFERENCE_ID    => 'sometimes|nullable|string|max:40',
        Entity::NARRATION       => 'sometimes|nullable|string|max:30|alpha_space_num',
    ];

    protected static $merchantPayoutRules = [
        Entity::PURPOSE         => 'required|string|max:30|in:payout',
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

    protected static $createPurposeRules = [
        Entity::PURPOSE      => 'required|filled|string|max:30|alpha_dash_space',
        Entity::PURPOSE_TYPE => 'required|filled|string|in:refund,settlement',
    ];

    protected static $merchantPayoutOnDemandRules = [
        Entity::AMOUNT   => 'required|integer|min:100',
        Entity::CURRENCY => 'required|size:3',
    ];

    protected static $bulkApproveRules = [
        Entity::PAYOUT_IDS       => 'required|array',
        Entity::PAYOUT_IDS. '.*' => 'required|public_id|size:19',
        User\Entity::OTP         => 'required|filled|min:4',
        User\Entity::TOKEN       => 'required|unsigned_id',
    ];

    protected static $bulkRejectRules = [
        Entity::PAYOUT_IDS       => 'required|array',
        Entity::PAYOUT_IDS. '.*' => 'required|public_id|size:19',
    ];

    protected static $fundAccountPayoutValidators = [
        'fund_account_mode',
    ];

    protected function validateMethod($attribute, $method)
    {
        Method::validateMethod($method);
    }

    protected function validateFundAccountMode($input)
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        //
        // We use mode from the entity and not from the input, because
        // in case of UPI, we set the mode to UPI in modifiers (called in build).
        // But this particular validateMode function is not called via build.
        // It's explicitly called later after build. Since we don't pass input by
        // reference to build, this function does not have the modified input.
        // Due to this, we would end up NOT validating mode for UPI.
        // Hence, we take the mode from the entity directly which would be filled by build.
        //
        $mode = $payout->getMode();

        $fundAccount = $payout->fundAccount;

        $accountType = $fundAccount->getAccountType();

        if (empty($mode) === true)
        {
            // Going forward, we want to make `mode` mandatory for all payouts, irrespective of anything.
            if ($accountType === FundAccount\Type::CARD)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The mode field is required for card payouts',
                    Entity::MODE,
                    [
                        'input' => $input
                    ]);
            }

            return;
        }

        Mode::validateModeOfAccountType($mode, $accountType);

        $this->validateCardAccountType($payout);

        $this->validateVpaAccountType($payout);

        $this->validateModeAndAmount($input, $payout);
    }

    protected function validateCardAccountType(Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $accountType = $fundAccount->getAccountType();

        if ($accountType === FundAccount\Type::CARD)
        {
            $cardIssuer = $fundAccount->account->getIssuer();

            Mode::validateModeOfIssuer($mode, $cardIssuer);
        }
    }

    protected function validateVpaAccountType(Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $accountType = $fundAccount->getAccountType();

        if (($accountType === FundAccount\Type::VPA) or
            ($mode === Mode::UPI))
        {
            $balance = $payout->balance;

            if (($balance->isTypeBanking() === true) and
                ($balance->getAccountType() === AccountType::DIRECT) and
                ($balance->getChannel() === Channel::RBL))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'UPI is not supported for RBL Banking Payouts currently',
                    Entity::MODE,
                    [
                        'balance_id'    => $balance->getId(),
                        'mode'          => $mode,
                        'account_type'  => $accountType,
                        'payout_id'     => $payout->getId(),
                    ]);
            }
        }
    }

    protected function validateModeAndAmount(array $input, Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $amount = $input[Entity::AMOUNT];

        $minRtgsAmount = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount = NodalAccount::MAX_IMPS_AMOUNT * 100;
        $maxUpiAmount  = FundAccount\Validator::MAX_UPI_AMOUNT;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)) or
            (($mode === Mode::UPI) and ($amount > $maxUpiAmount)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'          => $amount,
                    'mode'            => $mode,
                    'min_rtgs_amount' => $minRtgsAmount,
                    'max_imps_amount' => $maxImpsAmount,
                    'fund_account_id' => $fundAccount->getId(),
                    'account_type'    => $fundAccount->getAccountType(),
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
     *
     * @throws Exception\BadRequestException
     */
    public function validatePaymentForPayout(array $input, Payment\Entity $payment)
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

    public function validatePayoutStatusForApproveOrReject()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
                null,
                [
                    'id'     => $payout->getId(),
                    'status' => $payout->getStatus(),
                ]
            );
        }
    }

    public function validateRetryPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->hasPayment() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_FOR_PAYMENT_NOT_ALLOWED,
                null,
                [
                    'payout_id'     => $payout->getId(),
                    'payment_id'    => $payout->getPaymentId(),
                ]);
        }

        $payoutStatus = $payout->getStatus();

        if ($payoutStatus !== Status::REVERSED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_NOT_IN_REVERSED,
                null,
                [
                    'payout_id'     => $payout->getId(),
                    'payout_status' => $payoutStatus,
                ]);
        }
    }

    public function validateProcessingQueuedPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusQueued() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validateProcessingPendingPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validateRejectPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validateIsFundAccountPayout(Entity $payout)
    {
        if (($payout->hasFundAccount() === false) or
            ($payout->hasCustomer() === true))
        {
            throw new Exception\LogicException(
                'Payout is not of RX or not a proper fund_account type',
                null,
                [
                    'payout_id'         => $payout->getId(),
                    'balance_type'      => $payout->balance->getType(),
                    'fund_account_id'   => $payout->getFundAccountId(),
                    'customer_id'       => $payout->getCustomerId(),
                ]);
        }
    }

    public function validateCancel()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusQueued() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }
}
