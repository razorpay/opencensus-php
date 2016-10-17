<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $downWindow = (new Absence\Core)->create($input);

        return $downWindow->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $downWindow = (new Absence\Core)->edit($downWindow, $input);

        return $downWindow->toArrayPublic();

    }

    public function delete($id)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $this->repo->gateway_absence->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return ['message' => 'Gateway Absence successfully deleted'];
    }

    public function findAbsentGateways(array $input)
    {
        $gateways = $this->repo->gateway_absence->fetch($input);

        return $gateways->toArrayPublic();
    }
}
