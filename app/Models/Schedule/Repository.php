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
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
        Entity::ORG_ID          => 'sometimes|alpha_num',
        Entity::TYPE            => 'sometimes|string'
    ];

    public function getDailySettlementScheduleByDelay($delay)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE,   '=', Type::SETTLEMENT)
                    ->where(Entity::PERIOD, '=', Period::DAILY)
                    ->where(Entity::DELAY,  '=', $delay)
                    ->where(Entity::HOUR,   '=', Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_HOUR)
                    ->first();
    }

    public function fetchSettlementSchedules()
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, '=', Type::SETTLEMENT)
                    ->orderBy(Entity::NAME)
                    ->get();
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }

    public function getScheduleByPeriodIntervalAnchorAndType(string $period, int $interval, $anchor, string $type)
    {
         return $this->newQuery()
                     ->where(Entity::PERIOD, '=', $period)
                     ->where(Entity::INTERVAL, '=', $interval)
                     ->where(Entity::ANCHOR, '=', $anchor)
                     ->where(Entity::TYPE, '=', $type)
                     ->first();
    }

    public function getScheduleByPeriodIntervalAnchorDelayAndType(string $period,
                                                                  int $interval,
                                                                  $anchor,
                                                                  int $delay,
                                                                  string $type)
    {
        return $this->newQuery()
                    ->where(Entity::PERIOD, '=', $period)
                    ->where(Entity::INTERVAL, '=', $interval)
                    ->where(Entity::ANCHOR, '=', $anchor)
                    ->where(Entity::DELAY, '=', $delay)
                    ->where(Entity::TYPE, '=', $type)
                    ->first();
    }

    public function getScheduleByPeriodIntervalAnchorHourDelayAndType(string $period, int $interval, $anchor, int $hour, int $delay, string $type)
    {
         return $this->newQuery()
                     ->where(Entity::PERIOD, '=', $period)
                     ->where(Entity::INTERVAL, '=', $interval)
                     ->where(Entity::ANCHOR, '=', $anchor)
                     ->where(Entity::HOUR, '=', $hour)
                     ->where(Entity::DELAY, '=', $delay)
                     ->where(Entity::TYPE, '=', $type)
                     ->first();
    }

    public function fetchSchedulesByType(string $type)
    {
        return $this->newQuery()
            ->where(Entity::TYPE, '=', $type)
            ->orderBy(Entity::NAME)
            ->get();
    }

    public function fetchScheduleById(string $id)
    {
        return $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->firstOrFail();
    }
}
