<?php

namespace RZP\Models\Schedule\Task;

use RZP\Base;
use RZP\Models\Schedule\Task\Entity as ScheduleTask;
use RZP\Models\Payment\Method;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Schedule\Task\Type;

class Validator extends Base\Validator
{
    protected static $createRules = [
        ScheduleTask::TYPE              => 'required|string|max:20',
        ScheduleTask::METHOD            => 'sometimes|nullable|string|max:20|custom',
        ScheduleTask::SCHEDULE_ID       => 'required|alpha_dash|max:20',
        ScheduleTask::NEXT_RUN_AT       => 'sometimes|integer',
        ScheduleTask::ENTITY_ID         => 'sometimes|max:14',
        ScheduleTask::ENTITY_TYPE       => 'sometimes'
    ];

    protected static $updateNextRunAtRules = [
        ScheduleTask::TYPE              => 'required|custom',
        ScheduleTask::NEXT_RUN_AT       => 'sometimes|epoch',
    ];

    protected static $processTasksRules = [
        ScheduleTask::TYPE => 'required|string|max:20|custom',
    ];

    protected static $createValidators = [
        Entity::ENTITY_TYPE,
    ];

    protected function validateMethod($attribute, $method)
    {
        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $method);
        }
    }

    protected function validateType($attribute, $type)
    {
        if (Type::isValid($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Type given: ' . $type);
        }
    }

    public function validateEntityType(array $input)
    {
        $type = $input[Entity::TYPE];

        if (($type === Type::REPORTING) and (Type::isValidEntityType($type, $input[Entity::ENTITY_TYPE]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid Entity Type given for $type");
        }
    }
}
