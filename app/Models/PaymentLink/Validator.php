<?php

namespace RZP\Models\PaymentLink;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    /**
     * expiry_by has to be atleast 15 mins from current timestamp
     */
    const MIN_EXPIRY_SECS = 900;

    protected static $createRules = [
        Entity::RECEIPT       => 'required|string|min:1|max:40',
        Entity::AMOUNT        => 'required|mysql_unsigned_int|min:100',
        Entity::CURRENCY      => 'filled|in:INR',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::TITLE         => 'required|string|max:255',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes|nullable',
    ];

    protected static $editRules = [
        Entity::RECEIPT       => 'sometimes|string|min:1|max:40',
        Entity::EXPIRE_BY     => 'sometimes|epoch|nullable|custom',
        Entity::TITLE         => 'sometimes|string|max:255',
        Entity::DESCRIPTION   => 'sometimes|string|max:2048|nullable',
        Entity::NOTES         => 'sometimes|notes|nullable',
    ];

    public function validateExpireBy($attribute, $expireBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minExpireBy = $now->copy()->addSeconds(self::MIN_EXPIRY_SECS);

        if ($expireBy < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' .
                        $minExpireBy->diffForHumans($now) . ' the current time.';

            throw new BadRequestValidationFailureException($message);
        }
    }
}
