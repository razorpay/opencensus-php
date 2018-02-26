<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\Reporting;
use RZP\Models\Merchant\Account;
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

    public function getSettlementSchedules()
    {
        $schedules = $this->repo->schedule->fetchSettlementSchedules();

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

        $type = $input['type'];

        // In case of reporting type we need to call the reporting service
        if ($type === ScheduleTask\Type::REPORTING)
        {
            if (count($scheduleTasksToProcess) === 0)
            {
                return [];
            }

            $reportingService = new Reporting();

            $response = $reportingService->triggerSchedule($scheduleTasksToProcess);

            if (isset($response['error']) === false)
            {
                $successIds = $response['success_ids'];

                // We need to get all success_ids and mark their next run.
                foreach ($successIds as $successId)
                {
                    // We need to do a substr, as we need to strp `sched_`
                    $successId = substr($successId, 6);

                    $scheduleTask = $this->repo->schedule_task->fetchByEntity($successId);

                    $scheduleTask->updateNextRunAndLastRun(false);

                    $this->repo->saveOrFail($scheduleTask);
                }
            }

            return $response;
        }
        else
        {
            $entityNameSpace = Constants\Entity::getEntityNamespace($input['type']) . '\Core';

            return (new $entityNameSpace)->processTasks($scheduleTasksToProcess, $timestamp);
        }
    }
}
