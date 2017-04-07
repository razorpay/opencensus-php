<?php

namespace RZP\Models\Schedule\Task;

use Config;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task as ScheduleTask;

class Core extends Base\Core
{
    /**
     * create a default schedule for merchant
     */
    public function createDefaultSettlementSchedule($merchant)
    {
        $defaultDelay = Merchant\Entity::SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

        $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);

        $merchant->schedule()->associate($schedule);

        $input = [
            ScheduleTask\Entity::METHOD      => null,
            ScheduleTask\Entity::TYPE        => ScheduleTask\Type::SETTLEMENT,
            ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
        ];

        $this->createOrUpdate($merchant, $merchant, $input);
    }

    /**
     * Create a merchant schedule entity and deletes the existing entity if any
     */
    public function createOrUpdate(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $scheduleTask = $this->create($merchant, $entity, $input);

        $this->repo->transactionOnLiveAndTest(function() use ($scheduleTask)
        {
            if ($scheduleTask->isTypeSettlement() === true)
            {
                $this->createOrUpdateInMode($scheduleTask, Mode::LIVE);
                $this->createOrUpdateInMode($scheduleTask, Mode::TEST);
            }
            else
            {
                $this->createOrUpdateInMode($scheduleTask, $this->mode);
            }
        });

        $this->traceAndNotifyScheduleAssignment($scheduleTask);

        return $scheduleTask;
    }

    protected function createOrUpdateInMode($scheduleTask, $mode)
    {
        $scheduleTask->setConnection($mode);

        $currentSchedule = $this->repo
                                ->schedule_task
                                ->fetchDuplicate($scheduleTask, $mode);

        if ($currentSchedule !== null)
        {
            $this->repo->deleteOrFail($currentSchedule);
        }

        $this->repo->saveOrFail($scheduleTask);
    }

    /**
     * Creates merchant schedule entity
     */
    public function create(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        $scheduleTask = (new ScheduleTask\Entity)->build($input);

        $scheduleTask->merchant()->associate($merchant);

        $scheduleTask->entity()->associate($entity);

        $scheduleId = $input[ScheduleTask\Entity::SCHEDULE_ID];

        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $schedule = $this->repo->schedule->findByIdAndMerchantId($scheduleId, $merchantId);

        $scheduleTask->schedule()->associate($schedule);

        return $scheduleTask;
    }

    /**
     * Get All Settlement schedules assigned to merchant for payment method
     */
    public function getMerchantSettlementSchedule(Merchant\Entity $merchant, $method)
    {
        $scheduleTasks = $this->repo->schedule_task
                                  ->fetchByMerchant($merchant, Type::SETTLEMENT);

        $scheduleTask = $this->filterAndGetScheduleByMethodOrDefault(
                                    $scheduleTasks, $method);

        return $scheduleTask;
    }

    /**
     * Filter a schedule by method or default
     */
    protected function filterAndGetScheduleByMethodOrDefault(
        $scheduleTasks,
        $method)
    {
        $defaultScheduleTask = null;

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleMethod = $scheduleTask->getMethod();

            if ($scheduleMethod === $method)
            {
                return $scheduleTask;
            }
            else if ($scheduleMethod === null)
            {
                $defaultScheduleTask = $scheduleTask;
            }
        }

        return $defaultScheduleTask;
    }

    protected function traceAndNotifyScheduleAssignment($scheduleTask)
    {
        $data = $scheduleTask->toArrayPublic();

        $this->trace->info(TraceCode::SCHEDULE_ASSIGNED, $data);

        $user = $this->getInternalUsernameOrEmail();

        $this->slack->queue(
                "Schedule assigned to Merchant by $user",
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:',
                ]
            );
    }
}
