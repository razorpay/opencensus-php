<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create($input)
    {
        $downWindow = (new Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        return $downWindow;
    }

    public function edit($downWindow, $input)
    {
        $downWindow->edit($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_EDIT, $input);

        return $downWindow;
    }

}
