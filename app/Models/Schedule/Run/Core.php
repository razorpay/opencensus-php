<?php

namespace RZP\Models\Schedule\Run;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    /**
     * @param Schedule\Entity   $schedule
     * @param Base\PublicEntity $parentEntity
     * @param array             $input
     *
     * @return Entity
     */
    public function createRun(Schedule\Entity $schedule, Base\PublicEntity $parentEntity, array $input)
    {
        $this->trace->info(
            TraceCode::RUN_CREATE_REQUEST,
            [
                'input' => $input,
                'schedule_id' => $schedule->getId(),
            ]);

        $run = (new Entity)->build($input);

        $run->schedule()->associate($schedule);

        $parentEntity->run()->save($run);

        $this->repo->saveOrFail($run);

        return $run;
    }
}
