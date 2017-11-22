<?php

namespace RZP\Models\Payout;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD          => 'required|string',
        Entity::AMOUNT          => 'required|integer|max:500000000',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CUSTOMER_ID     => 'required|public_id',
        Entity::DESTINATION     => 'required|public_id',
    ];

    protected static $merchantRules = [
        Entity::MERCHANT_ID    => 'required|string|size:14',
        Entity::CUSTOMER_ID    => 'required|public_id',
        Entity::DESTINATION_ID => 'required|public_id',
        Entity::AMOUNT         => 'sometimes|integer|max:500000000',
        Entity::MIN_AMOUNT     => 'sometimes|integer|min:100',
        Entity::MODULO         => 'sometimes|integer|min:100',
        Entity::BUFFER_AMOUNT  => 'sometimes|integer|min:10000000'
    ];

    protected static $createValidators = [
        Entity::METHOD
    ];

    protected function validateMethod($input)
    {
        Method::validateMethod($input[Entity::METHOD]);
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
        // Only validating for card payments
        if ($payment->isCard() === false)
        {
            return;
        }

        $card = $payment->card;

        $payoutMethod      = $input[Entity::METHOD];
        $destinationEntity = Method::getEntityName($payoutMethod);

        if (($card->getType() === Card\Type::CREDIT) and
            ($destinationEntity === E::BANK_ACCOUNT))
        {
            throw new Exception\BadRequestException();
        }
    }
}
