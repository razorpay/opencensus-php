<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array           $input
     *
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createSchedule(array $input, $merchant = null)
    {
        $schedule = (new Entity)->build($input);

        $schedule->generateId();

        if ($merchant === null)
        {
            $merchant = $this->repo->merchant->getSharedAccount();
        }

        $schedule->merchant()->associate($merchant);

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
