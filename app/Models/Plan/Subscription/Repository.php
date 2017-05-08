<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Schedule\Task;

class Repository extends Base\Repository
{
    protected $entity = 'subscription';

    public function getSubscriptionsToCharge()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->whereIn(Entity::STATUS, [Status::ACTIVE, Status::AUTHENTICATED, Status::ON_HOLD])
                    ->whereNull(Entity::ENDED_AT)
                    ->where(Entity::AUTH_ATTEMPTS, '=', 0)
                    ->limit(100)
                    ->get();
    }

    public function getSubscriptionsToRetry()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->where(Entity::STATUS, '=', Status::OVERDUE)
                    ->where(Entity::ERROR_STATUS, Status::AUTH_FAILURE)
                    ->where(Entity::AUTH_ATTEMPTS, '>', 0)
                    ->where(Entity::AUTH_ATTEMPTS, '<', Charge::MAX_AUTH_ATTEMPTS)
                    ->limit(100)
                    ->get();
    }

    public function getSubscriptionsToExpire()
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->whereNotNull(Entity::START_AT)
                    ->where(Entity::START_AT, '<=', $currentTime)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->get();
    }

    protected function getBaseSubscriptionsQuery()
    {
        $subscriptionIdAttr = $this->dbColumn(Entity::ID);

        $taskEntityIdAttr = $this->repo->schedule_task->dbColumn(Task\Entity::ENTITY_ID);
        $taskNextRunAttr = $this->repo->schedule_task->dbColumn(Task\Entity::NEXT_RUN_AT);

        $subscriptionAttrs = $this->dbColumn('*');

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->select($subscriptionAttrs)
                    ->join(Table::SCHEDULE_TASK, $subscriptionIdAttr, '=', $taskEntityIdAttr)
                    ->where($taskNextRunAttr, '<', $currentTime)
                    ->where(function($query) use ($currentTime)
                        {
                            $query->whereNull(Entity::CURRENT_END)
                                  ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                        })
                    ->with(['plan', 'merchant']);
    }
}
