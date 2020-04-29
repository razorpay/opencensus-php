<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Schedule;
use RZP\Models\Merchant\Account;
use RZP\Models\Schedule\Task as ScheduleTask;

class Service extends Base\Service
{
    public function createSchedule($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_CREATE_REQUEST, $input);

        // By default we are creating schedule with type settlement if it's not in payload.
        // Once frontend change is done it will be mandatory
        $input[Schedule\Entity::TYPE] = $input[Schedule\Entity::TYPE] ?? Schedule\Type::SETTLEMENT;

        $schedule = (new Core)->createSchedule($input);

        $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getScheduleById($id)
    {
        $schedule = $this->repo->schedule->find($id);

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

        $schedule = $this->repo->schedule->find($id);

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

        $type = $input['type'];

        $scheduleTasksToProcess = $this->repo->schedule_task->fetchDueScheduleTasks($type, $timestamp);

        if (ScheduleTask\Type::isValidService($type) === true)
        {
            $serviceClass = ScheduleTask\Type::getServiceClass($type);

            return $serviceClass->processTasks($scheduleTasksToProcess);
        }
        else
        {
            $entityNameSpace = Constants\Entity::getEntityNamespace($input['type']) . '\Core';

            return (new $entityNameSpace)->processTasks($scheduleTasksToProcess, $timestamp);
        }
    }

    public function getScheduleTasks($type)
    {
        (new ScheduleTask\Validator)->validateInput('fetch', ['type' => $type]);

        $scheduleTasks = $this->repo->schedule_task->fetchByMerchant($this->merchant, $type);

        $scheduleDetails = [];

        $scheduleTasks->each(function ($scheduleTask) use (& $scheduleDetails)
        {
                array_push($scheduleDetails, [
                    ScheduleTask\Entity::METHOD                     => $scheduleTask->getAttribute(ScheduleTask\Entity::METHOD),
                    ScheduleTask\Entity::TYPE                       => $scheduleTask->getAttribute(ScheduleTask\Entity::TYPE),
                    Schedule\Entity::NAME                           => $scheduleTask->schedule->getAttribute(Schedule\Entity::NAME),
                    Schedule\Entity::PERIOD                         => $scheduleTask->schedule->getAttribute(Schedule\Entity::PERIOD),
                    Schedule\Entity::INTERVAL                       => $scheduleTask->schedule->getAttribute(Schedule\Entity::INTERVAL),
                    Schedule\Entity::ANCHOR                         => $scheduleTask->schedule->getAttribute(Schedule\Entity::ANCHOR),
                    Schedule\Entity::HOUR                           => [$scheduleTask->schedule->getAttribute(Schedule\Entity::HOUR)],
                    Schedule\Entity::DELAY                          => $scheduleTask->schedule->getAttribute(Schedule\Entity::DELAY),
                    ScheduleTask\Entity::INTERNATIONAL              => $scheduleTask->getAttribute(ScheduleTask\Entity::INTERNATIONAL),
                    Schedule\Entity::IS_EARLY_SETTLEMENT_SCHEDULE   => false,
                ]);

             return true;
        });

        if($type === Schedule\Type::SETTLEMENT)
        {
            list($addEarlySettlementSchedule, $featureList) = $this->repo->feature
                ->merchantOnEarlySettlement($this->merchant);

            if($addEarlySettlementSchedule === true)
            {
                $this->addEarlySettlementSchedule($scheduleDetails, $featureList);
            }
        }

        return $scheduleDetails;
    }

    public function createFeeRecoveryScheduleTask($input)
    {
        (new ScheduleTask\Validator)->validateInput(ScheduleTask\Validator::CREATE_FEE_RECOVERY_SCHEDULE_TASK, $input);

        $balanceId  = $input[ScheduleTask\Entity::BALANCE_ID];
        $scheduleId = $input[ScheduleTask\Entity::SCHEDULE_ID];

        $schedule = $this->repo->schedule->findOrFail($scheduleId);
        $balance = $this->repo->balance->findOrFailById($balanceId);

        (new ScheduleTask\Validator)->validateBalanceAndSchedule($balance, $schedule);

        $merchant = $balance->merchant;

        $input = [
            Task\Entity::TYPE          => Task\Type::FEE_RECOVERY,
            Task\Entity::SCHEDULE_ID   => $scheduleId,
        ];

        return (new Task\Core)->createOrUpdateForFeeRecovery($merchant, $balance, $input);
    }

    /***
     * @param array $scheduleDetails
     * @param array $featureList
     *
     * This methods adds early settlements schedule to the response of schedule tasks if the merchant has early
     * settlement enabled, as there is no schedule present for early settlement and it is added as a feature for
     * the merchant.
     */
    protected function addEarlySettlementSchedule(array & $scheduleDetails, array $featureList)
    {
        $hour = [];

        if(in_array(Feature\Constants::ES_AUTOMATIC, $featureList, true) === true)
        {
            array_push($hour, Schedule\Constants::NINE_AM, Schedule\Constants::FIVE_PM);

            if(in_array(Feature\Constants::ES_AUTOMATIC_THREE_PM, $featureList, true) === true)
            {
                array_push($hour, Schedule\Constants::THREE_PM);
            }
        }

        sort($hour);

        array_push($scheduleDetails, [
            ScheduleTask\Entity::METHOD                     => null,
            ScheduleTask\Entity::TYPE                       => Schedule\Type::SETTLEMENT,
            Schedule\Entity::NAME                           => 'Basic T+0',
            Schedule\Entity::PERIOD                         => Schedule\Period::HOURLY,
            Schedule\Entity::INTERVAL                       => 0,
            Schedule\Entity::ANCHOR                         => null,
            Schedule\Entity::HOUR                           => $hour,
            Schedule\Entity::DELAY                          => 0,
            ScheduleTask\Entity::INTERNATIONAL              => 0,
            Schedule\Entity::IS_EARLY_SETTLEMENT_SCHEDULE   => true,
        ]);
    }
}
