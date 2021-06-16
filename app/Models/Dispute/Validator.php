<?php

namespace RZP\Models\Dispute;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Admin\File;
use RZP\Models\Currency\Currency;
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
    ];

    protected static $editRules = [
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::STATUS                 => 'sometimes|string|custom',
        Entity::EXPIRES_ON             => 'sometimes|epoch',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::SKIP_DEDUCTION         => 'sometimes|boolean',
        Entity::COMMENTS               => 'sometimes|string|min:5|max:255|utf8',
        Entity::BACKFILL               => 'sometimes|boolean',
    ];

    protected static $processDisputeRefundRules = [
        'to'   => 'required|int',
        'from' => 'required|int'
    ];

    protected static $createValidators = [
        'deduct_onset_for_non_transactional_phases',
        'amount'
    ];

    protected static $editValidators = [
        'non_transactional_disputes_closure',
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
}
