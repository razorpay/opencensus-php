<?php

namespace RZP\Models\Schedule\Task;

use RZP\Base;
use RZP\Models\Schedule\Task\Entity as ScheduleTask;
use RZP\Models\Payment\Method;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ScheduleTask::TYPE              => 'required|string|max:20',
        ScheduleTask::METHOD            => 'sometimes|string|max:20|custom',
        ScheduleTask::SCHEDULE_ID       => 'required|alpha_dash|max:20',
        ScheduleTask::NEXT_RUN_AT       => 'sometimes|integer'
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
