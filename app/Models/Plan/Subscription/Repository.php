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
        $subscriptions = $this->getBaseSubscriptionsQuery()
                              ->whereIn(Entity::STATUS, [Status::ACTIVE, Status::AUTHENTICATED, Status::HALTED])
                              ->whereNull(Entity::ENDED_AT)
                              ->where(function($query)
                              {
                                  $query->where(Entity::AUTH_ATTEMPTS, '=', 0)
                                      ->orWhere(function($query)
                                      {
                                          $query->where(Entity::AUTH_ATTEMPTS, '=', Charge::MAX_AUTH_ATTEMPTS)
                                                ->where(Entity::STATUS, '=', Status::HALTED);
                                      });
                              })
                              ->limit(100)
                              ->get();

        return $subscriptions;
    }

    public function getSubscriptionsToRetry()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->where(Entity::STATUS, '=', Status::OVERDUE)
                    ->whereNotNull(Entity::ERROR_STATUS)
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
