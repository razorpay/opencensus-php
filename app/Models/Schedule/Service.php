<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Schedule\Task as ScheduleTask;

class Service extends Base\Service
{
    public function createSchedule($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_CREATE_REQUEST, $input);

        $schedule = (new Core)->createSchedule($input);

        $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getScheduleById($id)
    {
        $schedule = $this->repo->schedule->findByIdAndMerchantId($id, Account::SHARED_ACCOUNT);

        return $schedule->toArrayPublic();
    }

    public function deleteSchedule($id)
    {
        $this->trace->info(TraceCode::SCHEDULE_DELETE_REQUEST, ['schedule_id' => $id]);

        $count = $this->repo->schedule_task->fetchScheduleUsageCountById($id);

        if ($count > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_IN_USE,
                Entity::ID,
                [$id]);
        }

        $schedule = $this->repo->schedule->findOrFailPublic($id);

        $this->repo->schedule->deleteOrFail($schedule);

        $this->trace->info(TraceCode::SCHEDULE_DELETED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getAllSchedules($input)
    {
        $schedules = $this->repo->schedule->fetch($input);

        return $schedules->toArrayPublic();
    }

    public function editSchedule($id, $input)
    {
        $this->trace->info(TraceCode::SCHEDULE_EDIT_REQUEST, $input);

        $schedule = $this->repo->schedule->findByIdAndMerchantId($id, Account::SHARED_ACCOUNT);

        $schedule = (new Core)->editSchedule($schedule, $input);

        $this->trace->info(TraceCode::SCHEDULE_EDITED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function updateNextRun($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_INITIATED);

        (new ScheduleTask\Validator)->validateInput('updateNextRunAt', $input);

        $timestamp = $input['next_run_at'] ?? Carbon::now()->getTimestamp();

        $type = $input['type'];

        $scheduleTasks = $this->repo->schedule_task->fetchDueScheduleTasks($type, $timestamp);

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleTask->updateNextRunAt($timestamp);

            $this->repo->saveOrFail($scheduleTask);
        }

        return [
            'ids' => $scheduleTasks->getIds(),
        ];
    }

    public function processTasks(array $input): array
    {
        $this->trace->info(TraceCode::SCHEDULE_TASKS_PROCESS_REQUEST, $input);

        (new ScheduleTask\Validator)->validateInput('processTasks', $input);

        //all tasks which are due and less than time
        $timestamp = Carbon::now()->getTimestamp();

        $scheduleTasksToProcess = $this->repo->schedule_task->fetchDueScheduleTasks($input['type'], $timestamp);

        $entityNameSpace = Constants\Entity::getEntityNamespace($input['type']) . '\Core';

        return (new $entityNameSpace)->processTasks($scheduleTasksToProcess, $timestamp);
    }
}
