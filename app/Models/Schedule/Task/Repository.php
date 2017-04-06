<?php

namespace RZP\Models\Schedule\Task;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule\Task as ScheduleTask;

class Repository extends Base\Repository
{
    protected $entity = 'schedule_task';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID             => 'sometimes|alpha_dash|max:20',
        Entity::METHOD                  => 'sometimes|string|max:20',
        Entity::SCHEDULE_ID             => 'sometimes|alpha_dash|max:20',
    );

    /**
     * Returns merchant schedule if it matches the one passed in the argument.
     * Method and merchant_id has to be same for it to be duplicate
     */
    public function fetchDuplicate(ScheduleTask\Entity $scheduleTask)
    {
        $query = $this->newQuery()
                      ->merchantId($scheduleTask->getMerchantId())
                      ->where(ScheduleTask\Entity::TYPE, '=', $scheduleTask->getType());

        $method = $scheduleTask->getMethod();

        if ($method === null)
        {
            $query->whereNull(ScheduleTask\Entity::METHOD);
        }
        else
        {
            $query->where(ScheduleTask\Entity::METHOD, '=', $method);
        }

        return $query->first();
    }

    public function findByMerchantAndMethod(Merchant\Entity $merchant, $method)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId());

        if ($method === null)
        {
            $query->whereNull(ScheduleTask\Entity::METHOD);
        }
        else
        {
            $query->where(ScheduleTask\Entity::METHOD, '=', $method);
        }

        return $query->with('schedule')
                     ->first();
    }

    public function fetchByMerchant(Merchant\Entity $merchant, $type)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->where(ScheduleTask\Entity::TYPE, '=', $type)
                    ->with('schedule')
                    ->get();
    }

    public function fetchScheduleCountById(string $scheduleId)
    {
        return $this->newQuery()
                    ->where(ScheduleTask\Entity::SCHEDULE_ID, '=', $scheduleId)
                    ->count();
    }

    public function fetchExpiredScheduleTasks($type, $timestamp)
    {
        return $this->newQuery()
                    ->where(ScheduleTask\Entity::TYPE, '=', $type)
                    ->where(ScheduleTask\Entity::NEXT_RUN_AT, '<', $timestamp)
                    ->get();
    }
}
