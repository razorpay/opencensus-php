<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function createSchedule($input)
    {
        $schedule = (new Entity)->build($input);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }

    public function editSchedule($schedule, $input)
    {
        $schedule->fill($input);

        $schedule->saveOrFail();
    }
}
