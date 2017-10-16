<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account as Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'schedule';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = [
        self::WITH_TRASHED => 'sometimes|in:0,1',
    ];

    public function getDailySettlementScheduleByDelay($delay)
    {
        return $this->newQuery()
                    ->where(Entity::PERIOD, '=', Period::DAILY)
                    ->where(Entity::MERCHANT_ID, '=', Merchant::SHARED_ACCOUNT)
                    ->where(Entity::DELAY, '=', $delay)
                    ->first();
    }

    public function fetchSettlementSchedules()
    {
        // TODO: It looks ugly because it must. There is
        // no type in schedules, but we name them all pretty
        // consistently. Will think of a cleaner solution later.

        return $this->newQuery()
                    ->whereRaw(
                        Entity::NAME . " LIKE 'Hourly%' OR " .
                        Entity::NAME . " LIKE 'Basic%' OR " .
                        Entity::NAME . " LIKE '%PM' OR " .
                        Entity::NAME . " LIKE '%AM'")
                    ->get();
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }

    public function getScheduleByPeriodIntervalAndAnchor(string $period, int $interval, int $anchor)
    {
         return $this->newQuery()
                     ->where(Entity::PERIOD, '=', $period)
                     ->merchantId(Merchant::SHARED_ACCOUNT)
                     ->where(Entity::INTERVAL, '=', $interval)
                     ->where(Entity::ANCHOR, '=', $anchor)
                     ->first();
    }
}
