<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    /**
     * expiry_by has to be atleast 15 mins from current timestamp
     */
    const MIN_EXPIRY_SECS = 900;

    protected static $createRules = [
        Entity::AMOUNT        => 'required|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'filled|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT       => 'required|string|min:1|max:40',
        Entity::TITLE         => 'required|string|max:255|min:1',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|min:1|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE         => 'sometimes|string|max:255|nullable',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
    ];

    protected static $sendNotificationRules = [
        'emails'     => 'required_without:contacts|filled|array|size:1',
        'emails.*'   => 'required|email|max:255',
        'contacts'   => 'required_without:emails|filled|array|size:1',
        'contacts.*' => 'required|numeric|digits_between:8,11',
    ];

    public function validateExpireBy($attribute, $value)
    {
        $now = Carbon::now(Timezone::IST);

        $minExpireBy = $now->copy()->addSeconds(self::MIN_EXPIRY_SECS);

        if ($value < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' . $minExpireBy->diffForHumans($now) . ' the current time.';

            throw new BadRequestValidationFailureException(
                $message,
                Entity::EXPIRE_BY,
                [Entity::EXPIRE_BY => $value]);
        }
    }

    public function validateSendNotification(array $input)
    {
        if ($this->entity->isInActive() === true)
        {
            $message = 'Payment link is not active.';

            throw new BadRequestValidationFailureException($message);
        }

        $this->validateInput('sendNotification', $input);
    }
}
