<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * @param array           $input
     *
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createSchedule(array $input, Merchant\Entity $merchant = null)
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
