<?php

namespace RZP\Models\Schedule\Task;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Schedule;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Balance;
use RZP\Models\Schedule\Task\Type;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Schedule\Task\Entity as ScheduleTask;

class Validator extends Base\Validator
{
    const CREATE_FEE_RECOVERY_SCHEDULE_TASK = 'create_fee_recovery_schedule_task';

    protected static $createRules = [
        ScheduleTask::TYPE                => 'required|string|max:20',
        ScheduleTask::METHOD              => 'sometimes|nullable|string|max:20|custom',
        ScheduleTask::SCHEDULE_ID         => 'required|alpha_dash|max:20',
        ScheduleTask::NEXT_RUN_AT         => 'sometimes|integer',
        ScheduleTask::INTERNATIONAL       => 'sometimes|integer',
    ];

    protected static $updateNextRunAtRules = [
        ScheduleTask::TYPE              => 'required|custom',
        ScheduleTask::NEXT_RUN_AT       => 'sometimes|epoch',
    ];

    protected static $processTasksRules = [
        ScheduleTask::TYPE => 'required|string|max:20|custom',
    ];

    protected static $fetchRules = [
        ScheduleTask::TYPE => 'required|string|max:20|custom',
    ];

    protected static $createFeeRecoveryScheduleTaskRules = [
        Entity::BALANCE_ID  => 'required|string|size:14',
        Entity::SCHEDULE_ID => 'required|string|size:14',
    ];

    protected function validateMethod($attribute, $method)
    {
        if ((Method::isValid($method) === false) and
            ($method !== EntityConstant::SETTLEMENT_TRANSFER))
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

    public function validateBalanceAndSchedule(Balance\Entity $balance,
                                               Schedule\Entity $schedule)
    {
        if ($schedule->getType() !== Schedule\Type::FEE_RECOVERY)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_SCHEDULE_TYPE,
                null,
                [
                    Entity::SCHEDULE_ID => $schedule->getId(),
                ]);
        }

        if (($balance->isTypeBanking() === false) or
            ($balance->isAccountTypeDirect() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_BALANCE_TYPE,
                null,
                [
                    Entity::SCHEDULE_ID => $schedule->getId(),
                ]);
        }
    }

    public function validateForExternalServices(array $input)
    {
        if (isset($input[Entity::ENTITY_ID]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Entity ID should be present');
        }

        if (isset($input[Entity::ENTITY_TYPE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Entity Type should be present');
        }

        $this->validateEntityType($input);
    }

    protected function validateEntityType(array $input)
    {
        $type = $input[Entity::TYPE];

        if (($type === Type::REPORTING) and (Type::isValidEntityType($type, $input[Entity::ENTITY_TYPE]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid Entity Type given for $type");
        }
    }
}
