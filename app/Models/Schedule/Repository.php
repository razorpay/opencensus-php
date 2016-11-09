<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account as Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'schedule';

    public function fetchSchedulesWithDueRun($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::NEXT_RUN, '<', $timestamp)
                    ->get();
    }

    public function fetchDailySettlementSchedulesByDelay($delay)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, '=', Type::SETTLEMENT)
                    ->where(Entity::PERIOD, '=', Period::DAILY)
                    ->where(Entity::MERCHANT_ID, '=', Merchant::SHARED_ACCOUNT)
                    ->where(Entity::DELAY, '=', $delay)
                    ->first();
    }
}
