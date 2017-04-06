<?php

namespace RZP\Models\Schedule\Task;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task as ScheduleTask;

class Core extends Base\Core
{
    /**
     * Create a merchant schedule entity and deletes the existing entity if any
     */
    public function createOrUpdate(Merchant\Entity $merchant, Base\Entity $entity, $input)
    {
        return $this->repo->transaction(function() use ($merchant, $entity, $input)
        {
            $scheduleTask = $this->create($merchant, $entity, $input);

            $currentSchedule = $this->repo->schedule_task
                                    ->fetchDuplicate($scheduleTask);

            if ($currentSchedule !== null)
            {
                $this->repo->deleteOrFail($currentSchedule);
            }

            $this->repo->saveOrFail($scheduleTask);

            return $scheduleTask;
        });
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
}
