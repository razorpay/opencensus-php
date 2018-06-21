<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT        => 'required|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'filled|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'required|filled|string|max:40',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'filled|string|max:40',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $sendNotificationRules = [
        'emails'     => 'required_without:contacts|filled|array|size:1',
        'emails.*'   => 'required|email|max:255',
        'contacts'   => 'required_without:emails|filled|array|size:1',
        'contacts.*' => 'required|contact_syntax|digits_between:8,11',
    ];

    public function validateExpireBy(string $attribute, int $value)
    {
        $now = Carbon::now(Timezone::IST);
        $minExpireBy = $now->copy()->addSeconds(Entity::MIN_EXPIRY_SECS);

        if ($value < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' . $minExpireBy->diffForHumans($now) . ' current time.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateTimesPayable(string $attribute, int $value)
    {
        $paymentLink = $this->entity;

        if ($value < $paymentLink->getTimesPaid())
        {
            throw new BadRequestValidationFailureException(
                'Times payable cannot be less than the number of payments already made',
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
     * @throws BadRequestValidationFailureException
     */
    public function validateShouldActivationBeAllowed()
    {
        $paymentLink  = $this->entity;
        $timesPayable = $paymentLink->getTimesPayable();
        $expireBy     = $paymentLink->getExpireBy();

        if ($timesPayable !== null)
        {
            $this->validateTimesPayable(Entity::TIMES_PAYABLE, $timesPayable);
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

    public function validateIsViewable()
    {
        $paymentLink = $this->entity;
        $publicId    = $paymentLink->getPublicId();

        if ($paymentLink->isInactive() === true)
        {
            throw new BadRequestValidationFailureException("payment link with id: {$publicId} is inactive");
        }
    }

    /**
     * If amount is set for payment link, validates that amount of new payment request is same as expected
     * @param  Payment\Entity $payment
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
}
