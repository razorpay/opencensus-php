<?php

namespace RZP\Models\Schedule\Run;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    public function createRun(Schedule\Entity $schedule, array $input)
    {
        $this->trace->info(
            TraceCode::RUN_CREATE_REQUEST,
            [
                'input' => $input,
                'schedule_id' => $schedule->getId(),
            ]);

        $run = (new Entity)->build($input);

        $run->schedule()->associate($schedule);

        $this->repo->saveOrFail($run);

        return $run;
    }
}
