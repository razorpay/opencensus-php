<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\PaymentLink
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT        => 'sometimes|nullable|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'required_with:amount|nullable|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'required|filled|string|max:40',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::AMOUNT        => 'sometimes|nullable|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'required_with:amount|nullable|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'filled|string|max:40',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $createValidators = [
        'amount_currency'
    ];

    protected static $editValidators = [
        'amount_currency'
    ];

    protected static $sendNotificationRules = [
        'emails'     => 'required_without:contacts|filled|array|size:1',
        'emails.*'   => 'required|email|max:255',
        'contacts'   => 'required_without:emails|filled|array|size:1',
        'contacts.*' => 'required|contact_syntax|digits_between:8,11',
    ];

    public function validateExpireBy(string $attribute, int $value)
    {
        $now         = Carbon::now(Timezone::IST);
        $minExpireBy = $now->copy()->addSeconds(Entity::MIN_EXPIRY_SECS);

        if ($value < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' . $minExpireBy->diffForHumans($now) . ' current time.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Validates attribute for edit operation. Note that in edit we allow making of times_payable equal to number of
     * times_paid already and while doing so payment link goes to inactive status.
     *
     * @param string   $attribute
     * @param int|null $value
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTimesPayable(string $attribute, int $value = null)
    {
        $paymentLink = $this->entity;

        if (($value !== null) and ($value < $paymentLink->getTimesPaid()))
        {
            throw new BadRequestValidationFailureException(
                'Times payable should be greater than or equal to the number of payments already made',
                Entity::TIMES_PAYABLE,
                [
                    Entity::TIMES_PAYABLE => $value,
                ]);
        }
    }

    /**
     * Validate times_payable attribute for activation. For activation(unlike edit), it must be greater than times_paid
     *
     * @param int|null $value
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTimesPayableForActivation(int $value = null)
    {
        $paymentLink = $this->entity;

        if (($value !== null) and ($value <= $paymentLink->getTimesPaid()))
        {
            throw new BadRequestValidationFailureException(
                'Times payable should be greater than the number of payments already made',
                Entity::TIMES_PAYABLE,
                [
                    Entity::TIMES_PAYABLE => $value,
                ]);
        }
    }

    public function validateActivateOperation()
    {
        $paymentLink = $this->entity;

        if ($paymentLink->isActive() === true)
        {
            $message = 'Payment link cannot be activated as it is already active';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateDeactivateOperation()
    {
        $paymentLink = $this->entity;

        if ($paymentLink->isInactive() === true)
        {
            $message = 'Payment link cannot be deactivated as it is already inactive';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Validates that payment link's attributes are holding values that confirms to active state requirements.
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateShouldActivationBeAllowed()
    {
        $paymentLink  = $this->entity;
        $timesPayable = $paymentLink->getTimesPayable();
        $expireBy     = $paymentLink->getExpireBy();

        if ($timesPayable !== null)
        {
            $this->validateTimesPayableForActivation($timesPayable);
        }

        if ($expireBy !== null)
        {
            $this->validateExpireBy(Entity::EXPIRE_BY, $expireBy);
        }
    }

    public function validateSendNotification(array $input)
    {
        if ($this->entity->isInactive() === true)
        {
            throw new BadRequestValidationFailureException('Payment link is not active.');
        }

        $this->validateInput('sendNotification', $input);
    }

    /**
     * If amount is set for payment link, validates that amount of new payment request is same as expected
     *
     * @param  Payment\Entity $payment
     *
     * @throws BadRequestException
     */
    public function validatePaymentAmount(Payment\Entity $payment)
    {
        $paymentAmount     = $payment->getAmount();
        $paymentLinkAmount = $this->entity->getAmount();

        if (($paymentLinkAmount !== null) and ($paymentLinkAmount !== $paymentAmount))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_LINK_PAYMENT_AMOUNT_MISMATCH,
                Payment\Entity::AMOUNT,
                [
                    'expected' => $paymentLinkAmount,
                    'actual'   => $paymentAmount,
                ]);
        }
    }

    public function validateAmountCurrency(array $input)
    {
        $amountExists   = array_key_exists(Entity::AMOUNT, $input);
        $currencyExists = array_key_exists(Entity::CURRENCY, $input);

        // If both amount and currency are not sent, return
        if (($amountExists or $currencyExists) === false)
        {
            return;
        }

        // If only one of amount or currency are sent, fail the request
        if (($amountExists xor $currencyExists) === true)
        {
            throw new BadRequestValidationFailureException(
                'Both amount and currency fields must be sent together',
                Entity::CURRENCY,
                [
                    'input' => $input
                ]);
        }

        // When amount is not sent (or null), reject if currency is set
        if ((isset($input[Entity::AMOUNT]) === false) and
            (isset($input[Entity::CURRENCY]) === true))
        {
            throw new BadRequestValidationFailureException(
                'The currency should be null when amount is null',
                Entity::CURRENCY,
                [
                    'input' => $input
                ]);
        }
    }
}
