<?php

namespace RZP\Models\Schedule\Task;

use Carbon\Carbon;
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
                      ->where(Entity::TYPE, '=', $scheduleTask->getType())
                      ->where(Entity::INTERNATIONAL, '=', $scheduleTask->isInternational());

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

    public function findMerchantSettlementSchedule(Merchant\Entity $merchant, $method, bool $international = false)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId())
                      ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                      ->where(Entity::INTERNATIONAL, '=', $international);

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

    public function fetchByMerchantAndInternational(Merchant\Entity $merchant, $type, $international)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::INTERNATIONAL, '=', $international)
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

    public function fetchByMerchantOnConnection($merchant, $type, $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->merchantId($merchant->getId())
                    ->where(Entity::TYPE, '=', $type)
                    ->with('schedule')
                    ->get();
    }

    public function fetchActiveByMerchantWithTrashed(Merchant\Entity $merchant, $type, bool $trashed)
    {
        $currTimestamp = Carbon::now()->getTimestamp();

        $query = $this->newQuery()
            ->merchantId($merchant->getId())
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::NEXT_RUN_AT, '>', $currTimestamp)
            ->with('schedule');

        if($trashed)
        {
            $query = $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @param $oldScheduleId
     * @param $newScheduleId
     * @return mixed
     *
     * This function will update the schedule id
     * of a schedule from old id to the new one.
     *
     * Validate new id is a valid one.
     */
    public function updateScheduleId($oldScheduleId, $newScheduleId, bool $trashed)
    {
        $currTimestamp = Carbon::now()->getTimestamp();

        $query = $this->newQuery()
            ->where(Entity::TYPE, '=', Type::CDS_PRICING)
            ->where(Entity::NEXT_RUN_AT, '>', $currTimestamp)
            ->where(Entity::SCHEDULE_ID, '=', $oldScheduleId);

        if($trashed === true)
        {
            $query = $query->withTrashed();
        }

        return $query->update([Entity::SCHEDULE_ID => $newScheduleId]);
    }

    public function getScheduleTasksToRun(string $type, string $startTimestamp, string $endTimestamp)
    {
        $query = $this->newQuery()
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::NEXT_RUN_AT, '>=', $startTimestamp)
            ->where(Entity::NEXT_RUN_AT, '<=', $endTimestamp);

        return  $query->get();
    }
}
