<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createSchedule($input)
    {
        $schedule = (new Entity)->build($input);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }

    public function edit($schedule, $input)
    {
        $schedule->edit($input);

        $schedule->saveOrFail();

        return $schedule;
    }
}
