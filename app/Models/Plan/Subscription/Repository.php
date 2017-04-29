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
        //
        // We should be getting on_hold subscriptions also
        // so that we can create the invoice. We will not
        // charge these invoices.
        // TODO: Do this later.
        //

        $subscriptionIdAttr = $this->getAttributeWithTableName(Entity::ID);

        $taskEntityIdAttr = $this->manager->schedule_task->getAttributeWithTableName(Task\Entity::ENTITY_ID);
        $taskNextRunAttr = $this->manager->schedule_task->getAttributeWithTableName(Task\Entity::NEXT_RUN_AT);

        $subscriptionAttrs = $this->getAttributeWithTableName('*');

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->select($subscriptionAttrs)
                    ->whereIn(Entity::STATUS, [Status::ACTIVE, Status::AUTHENTICATED])
                    ->whereNull(Entity::ENDED_AT)
                    ->where(Entity::AUTH_ATTEMPTS, '=', 0)
                    ->join(Table::SCHEDULE_TASK, $subscriptionIdAttr, $taskEntityIdAttr)
                    ->where($taskNextRunAttr, '<', $currentTime)
                    ->where(function($query) use ($currentTime)
                            {
                                $query->whereNull(Entity::CURRENT_END)
                                      ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                            })
                    ->with(['plan', 'merchant'])
                    ->get();
    }

    public function getSubscriptionsToRetry()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->where(Entity::STATUS, '=', Status::OVERDUE)
                    ->where(Entity::ERROR_STATUS, Status::AUTH_FAILURE)
                    ->where(Entity::AUTH_ATTEMPTS, '>', 0)
                    ->where(Entity::AUTH_ATTEMPTS, '<', Charge::MAX_AUTH_ATTEMPTS)
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
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::CHARGE_AT, '<=', $currentTime)
                    ->where(function($query) use ($currentTime)
                            {
                                $query->whereNull(Entity::CURRENT_END)
                                      ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                            });
    }
}
