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
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40',
        Entity::TITLE         => 'sometimes|string|max:255',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes',
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
}
