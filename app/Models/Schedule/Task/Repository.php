<?php

namespace RZP\Models\Schedule\Task;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'schedule_task';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID             => 'sometimes|alpha_dash|max:20',
        Entity::METHOD                  => 'sometimes|string|max:20',
        Entity::SCHEDULE_ID             => 'sometimes|alpha_dash|max:20',
        Entity::TYPE                    => 'sometimes|alpha|max:12',
    );

    /**
     * Returns merchant schedule if it matches the one passed in the argument.
     * Method and merchant_id has to be same for it to be duplicate
     *
     * @param Entity $scheduleTask
     *
     * @return Entity
     */
    public function fetchExistingScheduleTask(Entity $scheduleTask)
    {
        $query = $this->newQuery()
                      ->merchantId($scheduleTask->getMerchantId())
                      ->where(Entity::ENTITY_ID, '=', $scheduleTask->getEntityId())
                      ->where(Entity::TYPE, '=', $scheduleTask->getType());

        $method = $scheduleTask->getMethod();

        if ($method === null)
        {
            $query->whereNull(Entity::METHOD);
        }
        else
        {
            $query->where(Entity::METHOD, '=', $method);
        }

        return $query->first();
    }

    public function findByMerchantAndMethod(Merchant\Entity $merchant, $method)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId());

        if ($method === null)
        {
            $query->whereNull(Entity::METHOD);
        }
        else
        {
            $query->where(Entity::METHOD, '=', $method);
        }

        return $query->with('schedule')
                     ->first();
    }

    public function fetchByMerchant(Merchant\Entity $merchant, $type)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->where(Entity::TYPE, '=', $type)
                    ->with('schedule')
                    ->get();
    }

    public function fetchScheduleUsageCountById(string $scheduleId)
    {
        return $this->newQuery()
                    ->where(Entity::SCHEDULE_ID, '=', $scheduleId)
                    ->count();
    }

    public function fetchByEntityAndMerchant(Base\PublicEntity $entity, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entity->getId())
                    ->merchantId($merchant->getId())
                    ->first();
    }

    public function fetchByEntity(string $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->first();
    }

    public function fetchDueScheduleTasks(string $type, int $timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::NEXT_RUN_AT, '<', $timestamp)
                    ->get();
    }
}
