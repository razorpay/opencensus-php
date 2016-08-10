<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\Terminal\Absence;

class Service extends Base\Service
{
    public function create($input, $gateway)
    {
        $downWindow = (new Absence\Core)->create($input, $gateway);

        return $downWindow->toArrayPublic();

    }

    public function getScheduleForGateway($gateway)
    {
        $schedule = $this->repo->gateway_absence->findForGateway($gateway);

        return $schedule->toArrayPublic();
    }

}
