<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create($input, $gateway)
    {
        $input['gateway'] = $gateway;

        $downWindow = (new Absence\Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE, $input);

        return $downWindow;
    }

    public function edit($downWindow, $input)
    {
        $downWindow->edit($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE, $input);

        return $downWindow;
    }

}
