<?php

namespace RZP\Models\Dispute;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY_DISPUTE_ID     => 'required|alpha_num',
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::PHASE                  => 'required|string|custom',
        Entity::RAISED_ON              => 'required|epoch',
        Entity::EXPIRES_ON             => 'required|epoch',
        Entity::REASON_ID              => 'required|alpha_num|size:14',
        Entity::AMOUNT                 => 'required|integer|min:100',
        Entity::DEDUCT_AT_ONSET        => 'sometimes|boolean',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_EMAILS        => 'sometimes|array',
        Entity::MERCHANT_EMAILS . '.*' => 'email',
        Entity::SKIP_EMAIL             => 'sometimes|boolean',
    ];

    protected static $editRules = [
        Entity::GATEWAY_DISPUTE_STATUS  => 'sometimes|string',
        Entity::STATUS                  => 'sometimes|string|custom',
        Entity::ACCEPTED_AMOUNT         => 'sometimes|integer|min:100',
        Entity::EXPIRES_ON              => 'sometimes|epoch',
        Entity::PARENT_ID               => 'sometimes|alpha_num|size:14',
    ];

    protected static $editValidators = [
        'non_transactional_disputes_closure',
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
                'Not a valid dispute status');
        }

        if ($this->entity->isClosed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE);
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

        if ($payment->getAmount() < $input[Entity::AMOUNT])
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT,
                Entity::AMOUNT,
                ['input' => $input, 'payment_id' => $payment->getId()]);
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

    protected function validateNonTransactionalDisputesClosure($input)
    {
        if (isset($input[Entity::STATUS]) === false)
        {
            return;
        }

        if (($this->entity->isNonTransactional() === true) and
            in_array($input[Entity::STATUS], Status::getTransactionalStatuses(),true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Disputes of non-transactional kinds can only be closed.',
                Entity::STATUS,
                $input);
        }
    }

    public function validateDeductOnsetForNonTransactionalPhase(array $input)
    {
        if (isset($input[Entity::DEDUCT_AT_ONSET]) === true)
        {
            if ((in_array($input[Entity::PHASE], Phase::getNonTransactionalPhases(),true) === true)
                and $input[Entity::DEDUCT_AT_ONSET] == true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Deduct at onset cannot be done for disputes in phase ' . $input[Entity::PHASE],
                    Entity::DEDUCT_AT_ONSET,
                    $input);
            }
        }
    }
}
