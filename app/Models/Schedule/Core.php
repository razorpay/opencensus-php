<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createSchedule($input)
    {
        $schedule = (new Entity)->build($input);

        $schedule->generateId();

        $schedule->setMerchantId(Account::SHARED_ACCOUNT);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }

    public function editSchedule($schedule, $input)
    {
        $schedule->edit($input);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }

    public function getOrCreateDefaultSchedule($delay) : Entity
    {
        $schedule = $this->repo->schedule->getDailySettlementScheduleByDelay($delay);

        if ($schedule === null)
        {
            $input = [
                Entity::NAME     => "Basic T$delay",
                Entity::TYPE     => Type::SETTLEMENT,
                Entity::PERIOD   => Period::DAILY,
                Entity::INTERVAL => 1,
                Entity::DELAY    => $delay,
            ];

            $schedule = $this->createSchedule($input);

            $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());
        }

        return $schedule;
    }
}
