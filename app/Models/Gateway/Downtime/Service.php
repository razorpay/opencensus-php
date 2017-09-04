<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime\Webhook;

class Service extends Base\Service
{
    protected $processor;

    public function create(array $input)
    {
        $downtime = $this->core()->create($input);

        return $downtime->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $downtime = $this->core()->edit($id, $input);

        return $downtime->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $absentGateways = $this->repo->gateway_downtime->fetch($input);

        return $absentGateways->toArrayPublic();
    }

    public function processGatewayDowntimeWebhook(string $source, array $input)
    {
        $processor = new Webhook\Processor($source);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_WEBHOOK, $input);

        $processor->validate($input);

        $data = $processor->process($input);

        return $data;
    }
}
