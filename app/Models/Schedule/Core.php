<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

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

    public function getOrCreateDefaultSchedule($delay)
    {
        $schedule = $this->repo->schedule->getDailySettlementScheduleByDelay($delay);

        if (is_null($schedule) === true)
        {
            $input = [
                Schedule\Entity::NAME     => "Basic T$requiredDelay",
                Schedule\Entity::TYPE     => Schedule\Type::SETTLEMENT,
                Schedule\Entity::PERIOD   => Schedule\Period::DAILY,
                Schedule\Entity::INTERVAL => 1,
                Schedule\Entity::DELAY    => $delay,
            ];

            $schedule = (new Schedule\Core)->createSchedule($input);

            $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());
        }

        return $schedule;
    }
}
