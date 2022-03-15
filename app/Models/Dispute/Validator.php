<?php

namespace RZP\Models\Dispute;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Admin\File;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\UniqueIdEntity;
use PhpParser\Node\Expr\AssignOp\Mod;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const OPERATION_MERCHANT_EDIT = 'merchant_edit';

    // Max allowed file size - 30MB (30*1024*1024).
    const MAX_FILE_SIZE = 31457280;

    const ACCEPTED_EXTENSIONS = [
        FileStore\Format::CSV,
        FileStore\Format::XLS,
        FileStore\Format::XLSX,
    ];

    protected static $createRules = [
        Entity::GATEWAY_DISPUTE_ID     => 'required|alpha_num',
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::PHASE                  => 'required|string|custom',
        Entity::RAISED_ON              => 'required|epoch',
        Entity::EXPIRES_ON             => 'required|epoch',
        Entity::REASON_ID              => 'required|alpha_num|size:14',
        Entity::AMOUNT                 => 'sometimes|integer|min:100',
        Entity::GATEWAY_AMOUNT         => 'sometimes|integer|min:1',
        Entity::GATEWAY_CURRENCY       => 'required_with:gateway_amount|string|size:3|custom',
        Entity::DEDUCT_AT_ONSET        => 'sometimes|boolean',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_EMAILS        => 'sometimes|array',
        Entity::MERCHANT_EMAILS . '.*' => 'filled|email',
        Entity::SKIP_EMAIL             => 'sometimes|boolean',
        Entity::BACKFILL               => 'sometimes|boolean',
        Entity::INTERNAL_RESPOND_BY    => 'sometimes|epoch',
    ];

    protected static $editRules = [
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::STATUS                 => 'sometimes|string|custom',
        Entity::INTERNAL_STATUS        => 'sometimes|string|custom',
        Entity::INTERNAL_RESPOND_BY    => 'sometimes|epoch',
        Entity::EXPIRES_ON             => 'sometimes|epoch',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::SKIP_DEDUCTION         => 'sometimes|boolean',
        Entity::COMMENTS               => 'sometimes|string|min:5|max:255|utf8',
        Entity::BACKFILL               => 'sometimes|boolean',
        Entity::DEDUCTION_SOURCE_TYPE  => 'required_with:deduction_source_id',
        Entity::DEDUCTION_SOURCE_ID    => 'required_with:deduction_source_type',
        Entity::DEDUCTION_REVERSAL_AT  => 'sometimes|epoch',
        Entity::RECOVERY_METHOD        => 'sometimes|in:adjustment,refund',
        Entity::ACCEPTED_AMOUNT        => 'sometimes|integer|min:100',
    ];

    protected static $processDisputeRefundRules = [
        'to'   => 'required|int',
        'from' => 'required|int'
    ];

    protected static $createValidators = [
        'deduct_onset_for_non_transactional_phases',
        'deduct_onset_recovery_method',
        'amount'
    ];

    protected static $editValidators = [
        'non_transactional_disputes_closure',
        'internal_status_transition',
        'deduction_source_type_and_id',
        'deduction_reversal_at',
        'recovery_method',
    ];

    protected static $merchantEditRules = [
        Entity::ACCEPT_DISPUTE         => 'sometimes|boolean',
        Entity::SUBMIT                 => 'sometimes|boolean',
        Entity::BACKFILL               => 'sometimes|boolean',
    ];

    protected function validatePhase(string $attribute, string $value)
    {
        if (Phase::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute phase: ' . $value);
        }
    }

    protected function validateStatus(string $attribute, string $value)
    {
        if (Status::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute status: ' . $value);
        }

        /** there is a usecase to support dispute edit in lost state
         * where internal_status moves from lost_merchant_not_debited to lost_merchant_debited
         *this if condition is to support for that case
         */
        if (($value === $this->entity->getStatus() and
            ($value === Status::LOST)) and
            ($this->entity->getInternalStatus() === InternalStatus::LOST_MERCHANT_NOT_DEBITED))
        {
            return;
        }

        if ($this->entity->isClosed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE);
        }
    }

    protected function validateGatewayCurrency($attribute, $currency)
    {
        if (Currency::isSupportedCurrency($currency) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }

    public function validatePaymentForDispute(array $input, Payment\Entity $payment)
    {
        if ($payment->isDisputed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE,
                null,
                ['input' => $input, 'payment_id' => $payment->getId()]);
        }

        //
        // This function is called before the build validator
        // Hence, if amount is set then we validate else we let
        // the build validator take care of it
        //
        if (isset($input[Entity::AMOUNT]) === true)
        {
            if ($payment->getAmount() < $input[Entity::AMOUNT])
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT,
                    Entity::AMOUNT,
                    ['input' => $input, 'payment_id' => $payment->getId()]);
            }
        }
    }

    protected function validateMerchantForDisputeDeductAtOnset($input, $payment)
    {
        if ((isset($input[Entity::DEDUCT_AT_ONSET]) === false) or
            ($input[Entity::DEDUCT_AT_ONSET]) === false)
        {
            return;
        }

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DEDUCT_DISPUTE) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deduct At Onset Dispute can not be created for EXCLUDE_DEDUCT_DISPUTE feature enable Merchant');
        }

        $mcc = $merchant->getCategory();

        if (in_array($mcc, Constants::MCC_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deduct At Onset Dispute can not be created for this Merchant Category');
        }

        $category2 = $merchant->getCategory2();

        if (in_array($category2, Constants::CATEGORY2_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deduct At Onset Dispute cannot be created when category2 is ' . $category2);
        }
    }

    public function validatePaymentAndMerchantForDispute($input, $payment)
    {
        $this->validatePaymentForDispute($input, $payment);

        $this->validateMerchantForDisputeDeductAtOnset($input, $payment);
    }

    public function validateInputBeforeBuild(array $input)
    {
        if (empty($input[Entity::REASON_ID]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'reason_id should be sent in the request to create a dispute.',
                Entity::REASON_ID,
                $input);
        }
    }

    public function validateBulkDisputeRequest(array $input)
    {
        if (empty($input[File\Core::FILE]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'file should be attached in the request to create disputes in bulk',
                File\Core::FILE,
                $input);
        }
    }

    /**
     *  We ensured via $editRules that $input[Entity::ACCEPTED_DISPUTE_AMOUNT] must be positive value.
     *  Here we put an upper limit to value of same.
     *
     * @param int $disputedAmount
     * @param array $input
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAcceptedDisputeAmount(int $disputedAmount, array $input)
    {
        if ($input[Entity::ACCEPTED_AMOUNT] > $disputedAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Accepted chargeback amount cannot be greater than disputed amount.',
                Entity::ACCEPTED_AMOUNT,
                $input);
        }

        if (($this->entity->payment->isInternational() === true) or
            ($this->entity->payment->getCurrency() !== Currency::INR))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Partial dispute accept not supported for non-inr payments.',
                Entity::ACCEPTED_AMOUNT,
                $input);
        }
    }

    public function validateDisputeCanBecomeParent()
    {
        if ($this->entity->child !== null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The parent dispute is linked to another dispute entity.',
                Entity::PARENT_ID);
        }
    }

    public function validateForMerchantUpdate(array $input)
    {
        if ($this->entity->getStatus() !== Status::OPEN)
        {
            throw new BadRequestValidationFailureException(
                'Disputes can only be modified when in open status');
        }

        if ((isset($input[Entity::ACCEPT_DISPUTE]) === true) and
            (isset($input[Entity::SUBMIT]) === true))
        {
            throw new BadRequestValidationFailureException(
                'Only one of the fields `accept_dispute` and `submit` can be sent');
        }
    }

    protected function validateDeductOnsetRecoveryMethod($input)
    {
        if ((isset($input[Entity::DEDUCT_AT_ONSET]) === false) or
            ($input[Entity::DEDUCT_AT_ONSET]) === false)
        {
            return;
        }

        $tempEntity = clone  $this->entity;

        $tempEntity->fill($input);

        $recoveryMethod = (new Core)->getRecoveryMethodForDisputeAccept($tempEntity);

        if ($recoveryMethod === RecoveryMethod::ADJUSTMENT)
        {
            return;
        }

        throw new BadRequestValidationFailureException('Deduct at onset not supported when recovery method is not adjustment',
        'deduct_at_onset',
        [
            'mapped_recovery_method' => $recoveryMethod
        ]);
    }

    protected function validateNonTransactionalDisputesClosure($input)
    {
        if (isset($input[Entity::STATUS]) === false)
        {
            return;
        }

        if ($this->entity->isNonTransactional() === false)
        {
            return;
        }

        if (in_array($input[Entity::STATUS], Status::getTransactionalStatuses(), true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non-transactional disputes can only be closed.',
                Entity::STATUS,
                $input);
        }
    }

    public function validateDeductionSourceTypeAndId(array $input)
    {
        if ((isset($input[Entity::DEDUCTION_SOURCE_ID]) === false) or
            (isset($input[Entity::DEDUCTION_SOURCE_TYPE])) === false)
        {
            return;
        }

        $tempEntity = (clone $this->entity)->fill($input);

        $this->validateStatusForDeductionSourceTypeAndId($tempEntity);

        $this->validateSkipDeductionForDeductionSourceTypeAndId($input);

        $this->validateReferentialIntegrityForDeductionSourceTypeAndID($input);
    }

    public function validateDeductOnsetForNonTransactionalPhases(array $input)
    {
        if (empty($input[Entity::DEDUCT_AT_ONSET]) === true)
        {
            return;
        }

        $nonTransactionalPhases = Phase::getNonTransactionalPhases();

        if (in_array($input[Entity::PHASE], $nonTransactionalPhases,true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deduct at onset cannot be done for disputes in phase ' . $input[Entity::PHASE],
                Entity::DEDUCT_AT_ONSET,
                $input);
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateInternalStatusTransition($input)
    {
        if (isset($input[Entity::INTERNAL_STATUS]) === false)
        {
            return;
        }

        if (($input[Entity::INTERNAL_STATUS] === InternalStatus::LOST_MERCHANT_NOT_DEBITED) and
            ($this->entity->getDeductAtOnset() === true))
        {
            throw new BadRequestValidationFailureException('Invalid internal status provided as merchant is already debited for dispute');
        }


        $this->validateInternalStatusTransitionByStateMachine($input);

    }

    public function validateAmount(array $input)
    {
        if ((isset($input['amount']) === true) and
            (isset($input['gateway_amount']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'amount and gateway_amount cannot be sent together');
        }

        if ((isset($input['amount']) === false) and
            (isset($input['gateway_amount']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Either amount or gateway_amount is required');
        }
    }

    // Checks if values are same in both arrays irrespective of the order for non-associative arrays
    public function validateArrayEqual(array $a, array $b) : bool
    {
        return ((count($a) === count($b)) and (array_diff($a, $b) === array_diff($b, $a)));
    }

    /**
     * Return y/Y value to true, n/N to false and other values as validation failures
     *
     * @param $res
     * @return bool
     * @throws BadRequestValidationFailureException
     */
    public function validateCustomBoolean($res) : bool
    {
        $res = trim(strtoupper($res));

        switch ($res)
        {
            case 'Y':
                return true;

            case 'N':
                return false;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Skip field value should be Y/N'
                );
        }
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension is as expected.
     *
     * @param $file
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkDisputesFile($file)
    {
        if ($file->getSize() > self::MAX_FILE_SIZE)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File Size exceeds max allowed size of 30MB'
            );
        }

        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, self::ACCEPTED_EXTENSIONS, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid File extension. Only ' . implode(', ', self::ACCEPTED_EXTENSIONS) . ' file formats are allowed'
            );
        }
    }

    public function validateGatewayAmount(Entity $dispute)
    {
        if (($dispute->payment->isInternational() === false) and
            ($dispute->payment->getCurrency() === Currency::INR))
        {
            if (($dispute->payment->getCurrency() === $dispute->getGatewayCurrency()) and
                ($dispute->getGatewayAmount() > $dispute->payment->getBaseAmount()))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Dispute gateway amount cannot exceed payment amount');
            }
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateInternalStatus($attribute, $value)
    {
        InternalStatus::validate($value);
    }

    protected function validateRecoveryMethod($input)
    {
        if ($this->entity->getDeductAtOnset() === true)
        {
            return;
        }

        if ((isset($input[Entity::INTERNAL_STATUS]) === true) and
            ($input[Entity::INTERNAL_STATUS] === InternalStatus::LOST_MERCHANT_DEBITED))
        {
            $currentInternalStatus = $this->entity->getInternalStatus();

            //if internal_status is lost_merchant_debited than recovery_method has to be set, except when current status is lost_merchant_not_debited and skip_deduction is true)
            if (($currentInternalStatus === InternalStatus::LOST_MERCHANT_NOT_DEBITED) and
                isset($input[Entity::SKIP_DEDUCTION]) and (boolval($input[Entity::SKIP_DEDUCTION]) === true))
            {
                if (isset($input[Entity::RECOVERY_METHOD]) === true)
                {
                    throw new BadRequestValidationFailureException('Recovery Method is not supported with Skip Deduction');
                }

                return;
            }

            //if recovery_method is present than internal_status has to be lost_merchant_debited
            if (isset($input[Entity::RECOVERY_METHOD]) === false)
            {
                throw new BadRequestValidationFailureException('Recovery Method is required for given Internal Status');
            }

            return;
        }

        if (isset($input[Entity::RECOVERY_METHOD]) === true)
        {
            throw new BadRequestValidationFailureException('Recovery Method not supported for given Internal Status');
        }
    }

    protected function validateDeductionReversalAt($input)
    {
        if (isset($input[Entity::DEDUCTION_REVERSAL_AT]) === false)
        {
            return;
        }

        $tempEntity = clone  $this->entity;

        $tempEntity->fill($input);

        if ($tempEntity->getInternalStatus() !== InternalStatus::REPRESENTED)
        {
            throw new BadRequestValidationFailureException('cannot set deduction_reversal_at when internal_status is not represented');
        }

        if ($tempEntity->getDeductAtOnset() === false)
        {
            throw new BadRequestValidationFailureException('cannot set deduction_reversal_at for dispute which is not deducted at onset');
        }


    }

    protected function shouldThrowExceptionOnInvalidInternalStatusTransition($input): bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['basicauth']->getMode() ?? Mode::LIVE;

        $variant = $app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(),
            'THROW_EXCEPTION_INVALID_INTERNAL_STATUS_TRANSITION', $mode);


        return ($variant !== 'control');
    }

    protected function validateStatusForDeductionSourceTypeAndId($tempEntity): void
    {
        if ($tempEntity->getInternalStatus() === InternalStatus::LOST_MERCHANT_DEBITED)
        {
            return;
        }

        throw new BadRequestValidationFailureException('deduction_source_type/deduction_source_id can be set
        only when internal_status is "lost_merchant_not_debited"');
    }

    protected function validateReferentialIntegrityForDeductionSourceTypeAndID(array $input)
    {
        $app = App::getFacadeRoot();

        $entityType = $input[Entity::DEDUCTION_SOURCE_TYPE];

        $entityId = $input[Entity::DEDUCTION_SOURCE_ID];

        $app['repo']->$entityType->findOrFailPublic($entityId);
    }

    protected function validateSkipDeductionForDeductionSourceTypeAndId(array $input)
    {
        if ((isset($input[Entity::SKIP_DEDUCTION]) === true) and
            (boolval($input[Entity::SKIP_DEDUCTION]) === true))
        {
            return;
        }

        throw new BadRequestValidationFailureException('skip_deduction should be true if overriding deduction_source_id
        and deduction_source_type');
    }

    protected function validateInternalStatusTransitionByStateMachine($input): void
    {
        $shouldThrowException = $this->shouldThrowExceptionOnInvalidInternalStatusTransition($input);

        $currentInternalStatus = $this->entity->getInternalStatus();

        $nextInternalStatus = $input[Entity::INTERNAL_STATUS];

        try
        {
            InternalStatus::validateNextInternalStatusForCurrentInternalStatus($currentInternalStatus, $nextInternalStatus);
        }
        catch (BadRequestValidationFailureException $exception)
        {
            if ($shouldThrowException === true)
            {
                throw  $exception;
            }

            $this->getTrace()->traceException($exception);
        }
    }
}
