<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const OPERATION_ACTIVATE = 'activate';

    /**
     * expiry_by has to be atleast 15 mins from current timestamp
     */
    const MIN_EXPIRY_SECS = 900;

    protected static $createRules = [
        Entity::AMOUNT        => 'required|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'filled|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT       => 'required|string|min:1|max:40',
        Entity::TITLE         => 'required|string|max:255|min:1',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|min:1|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'sometimes|string|max:255',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $activateRules = [
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40',
        Entity::TITLE         => 'sometimes|string|max:255',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $sendNotificationRules = [
        'emails'     => 'required_without:contacts|filled|array|size:1',
        'emails.*'   => 'required|email|max:255',
        'contacts'   => 'required_without:emails|filled|array|size:1',
        'contacts.*' => 'required|contact_syntax|digits_between:8,11',
    ];

    public function validateExpireBy($attribute, $value)
    {
        if ($value === null)
        {
            return;
        }

        $now = Carbon::now(Timezone::IST);

        $minExpireBy = $now->copy()->addSeconds(Entity::MIN_EXPIRY_SECS);

        if ($value < $minExpireBy->getTimestamp())
        {
            throw new BadRequestValidationFailureException('expire_by should be at least ' .
                $minExpireBy->diffForHumans($now) . ' ahead of the current time.');
        }
    }

    public function validateTimesPayable($attribute, $value)
    {
        if ($value === null)
        {
            return;
        }

        if (($value < $this->entity->getTimesPaid()) === true)
        {
            throw new BadRequestValidationFailureException(
                'Times payable cannot be less than the number of payments processed',
                Entity::TIMES_PAYABLE,
                ['times_payable' => $value]);
        }
    }

    /**
     * For activation of payment link, we only consider captured payments.
     * This is because, in dashboard, we will recommend the merchant to enter
     * a value greater than times_paid, and even after that, if it fails
     * because of succeeding payments being accommodated, it leaves a bad
     * user experience.
     * Also, this method allows merchant to make controlled changes to
     * times_payable, without being susceptible to payments velocity.
     *
     * @param Entity $paymentLink
     * @param array $input
     * @throws BadRequestValidationFailureException
     */
    public function validatePaymentForActivate(Entity $paymentLink, array $input)
    {
        if (isset($input[Entity::TIMES_PAYABLE]) === false)
        {
            return;
        }

        if ($input[Entity::TIMES_PAYABLE] <= $paymentLink->getTimesPaid())
        {
            throw new BadRequestValidationFailureException(
                'To activate, times payable value must be greater than the
                number of payments already processed',
                Entity::TIMES_PAYABLE,
                $input);
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
}
