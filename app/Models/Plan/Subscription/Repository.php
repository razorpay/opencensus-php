<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function getSubscriptionsToCharge()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->whereIn(Entity::STATUS, [Status::PROCESSED, Status::ACTIVE])
                    ->whereNull(Entity::ENDED_AT)
                    ->where(Entity::AUTH_ATTEMPTS, 0)
                    ->get();
    }

    public function getSubscriptionsToRetry()
    {
        return $this->getBaseSubscriptionsQuery()
                    ->where(Entity::STATUS, '=', Status::ON_HOLD)
                    ->where(Entity::ERROR_STATUS, Status::AUTH_FAILURE)
                    ->where(Entity::AUTH_ATTEMPTS, '>', 0)
                    ->where(Entity::AUTH_ATTEMPTS, '<', Charge::MAX_AUTH_ATTEMPTS)
                    ->get();
    }

    protected function getBaseSubscriptionsQuery()
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::CHARGE_AT, '<', $currentTime)
                    ->where(function($query) use ($currentTime)
                            {
                                $query->whereNull(Entity::CURRENT_END)
                                      ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                            });
    }
}