<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\Absence;

class Service extends Base\Service
{
    public function create($input, $gateway)
    {
        $down_window = (new Absence\Core)->create($input, $gateway);

        return $down_window->toArrayPublic();

    }

    public function getScheduleForGateway($gateway)
    {
        $schedule = $this->repo->terminal_absence->findForGateway($gateway);

        return $schedule->toArrayPublic();
    }

    public function getSchedulesBetween($from, $to)
    {
        $schedule = $this->repo->terminal_absence->findBetweenTimestampsForGateway($from, $to);

        return $schedule->toArrayPublic();
    }
}
