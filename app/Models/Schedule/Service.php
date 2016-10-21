<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createSchedule($input)
    {
        $schedule = (new Core)->createSchedule($input);

        $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getScheduleById($id)
    {
        $schedule = $this->repo->schedule->getScheduleByIdAndOwnerId($id, $this->merchant->getId());

        return $schedule->toArrayPublic();
    }

    public function editSchedule($id, $input)
    {
        $schedule = $this->repo->schedule->getScheduleByIdAndOwnerId($id, $this->merchant->getId());

        $schedule = (new Schedule\Core)->editSchedule($schedule, $input);

        $this->trace->info(TraceCode::SCHEDULE_EDITED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }
}
