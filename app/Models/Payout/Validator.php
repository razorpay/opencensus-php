<?php

namespace RZP\Models\Payout;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD          => 'required|string',
        Entity::AMOUNT          => 'required|integer|max:100000000',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CUSTOMER_ID     => 'required|public_id',
        Entity::DESTINATION     => 'required|public_id',
    ];

    protected static $merchantRules = [
        Entity::MERCHANT_ID    => 'required|string|size:14',
        Entity::CUSTOMER_ID    => 'required|public_id',
        Entity::DESTINATION_ID => 'required|public_id',
        Entity::AMOUNT         => 'sometimes|integer|max:100000000',
        Entity::MIN_AMOUNT     => 'sometimes|integer|min:100',
        Entity::MODULO         => 'sometimes|integer|min:100',
    ];

    protected static $createValidators = [
        Entity::METHOD
    ];

    protected function validateMethod($input)
    {
        Method::validateMethod($input[Entity::METHOD]);
    }

    public function validatePaymentPayout($input, $payment)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        $payoutAmount = $input['amount'];

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
}
