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
        Entity::PHASE                  => 'required|string',
        Entity::RAISED_ON              => 'required|epoch',
        Entity::EXPIRES_ON             => 'required|epoch',
        Entity::REASON_ID              => 'required|alpha_num|size:14',
        Entity::AMOUNT                 => 'required|integer|min:100',
        Entity::DEDUCT_AT_ONSET        => 'required|boolean',
    ];

    protected static $createValidators = [
        Entity::PHASE,
    ];

    protected function validatePhase(array $input)
    {
        if (Phase::exists($input[Entity::PHASE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute phase: '.$input[Entity::PHASE]);
        }
    }

    public function validatePaymentForDispute(array $input, Payment\Entity $payment)
    {
        if ($payment->isDisputed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE);
        }

        if ($payment->getAmount() < $input[Entity::AMOUNT])
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT);
        }
    }

    public function validateInputBeforeBuild(array $input)
    {
        if (empty($input[Entity::REASON_ID]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'reason_id should be sent in the request to create a dispute.',
                Entity::REASON_ID);
        }
    }
}
