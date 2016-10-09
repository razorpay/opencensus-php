<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function getSubscriptionsToCharge()
    {
        /**
         * select * from subscriptions where charge_at <= 'current_time'
         */

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::CHARGE_AT, '<', $currentTime)
                    ->get();
    }
}