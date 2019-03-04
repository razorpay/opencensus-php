<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Database\Eloquent\Collection;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::METHOD_DOWNTIME_CREATE,
            $input
        );

        $downtime = (new Entity)->build($input);

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    public function edit(Entity $downtime, array $input): Entity
    {
        $this->trace->info(TraceCode::METHOD_DOWNTIME_EDIT, $input);

        $downtime->edit($input);

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    public function createFromGatewayDowntimes(array $input)
    {
        $gatewayDowntimes = $this->repo->gateway_downtime->fetchCurrentAndFutureDowntimes();

        $this->processUpi($gatewayDowntimes);
    }

    protected function processUpi(Collection $gatewayDowntimes)
    {
        (new UpiProcessor)->process($gatewayDowntimes);
    }
}
