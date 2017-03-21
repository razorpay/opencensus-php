<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Base;
use RZP\Models\Merchant\Schedule\Entity as MerchantSchedule;
use RZP\Models\Payment\Method;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        MerchantSchedule::TYPE              => 'required|string|max:20',
        MerchantSchedule::METHOD            => 'sometimes|string|max:20|custom',
        MerchantSchedule::SCHEDULE_ID       => 'required|alpha_dash|max:20',
        MerchantSchedule::NEXT_RUN_AT       => 'sometimes|integer'
    ];

    protected function validateMethod($attribute, $method)
    {
        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $method);
        }
    }
}
