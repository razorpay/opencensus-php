<?php

namespace RZP\Models\Reversal;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Transfer;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT      => 'required|integer|min:100',
        Entity::CURRENCY    => 'required|string|size:3|in:INR',
        Entity::NOTES       => 'sometimes|notes',
    ];

    public function validateReversalAmount(Transfer\Entity $transfer, array $input)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        $amount = $input['amount'];

        if (empty($amount) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount cannot be blank',
                'amount');
        }

        if ((ctype_digit($amount) === false) and
            (is_int($amount) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount should be in paise and only have digits',
                Entity::AMOUNT);
        }

        $transferAmount = $transfer->getAmount();

        if ($amount > $transferAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED,
                'amount',
                ['transfer_id' => $transfer->getId()]);
        }

        if ($amount > $transfer->getAmountUnreversed())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED,
                'amount',
                ['transfer_id' => $transfer->getId()]);
        }
    }
}
