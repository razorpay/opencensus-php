<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;

class Core extends Base\Core
{
    /**
     * @param array           $input
     *
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createSchedule(array $input)
    {
        $this->trace->info(
            TraceCode::SCHEDULE_CREATE_REQUEST,
            $input
        );

        if (array_key_exists('org_id', $input) && $input['org_id'] != null)
        {
            Org\Entity::verifyIdAndStripSign($input['org_id']);
        }

        $schedule = (new Entity)->build($input);

        $schedule->generateId();

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
                Entity::PERIOD   => Period::DAILY,
                Entity::INTERVAL => 1,
                Entity::DELAY    => $delay,
                Entity::HOUR     => Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_HOUR,
            ];

            $schedule = $this->createSchedule($input);

            $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());
        }

        return $schedule;
    }
}
