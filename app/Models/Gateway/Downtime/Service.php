<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Gateway\Downtime\WebhookProcessor;

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

    public function delete($id)
    {
        $downtime = $this->core()->delete($id);

        return $downtime->toArrayDeleted();
    }

    public function fetchMultiple(array $input)
    {
        $absentGateways = $this->repo->gateway_downtime->fetch($input);

        return $absentGateways->toArrayPublic();
    }

    public function processStatusCakeCallback(array $input)
    {
        $data = (new WebhookProcessor\StatusCakeProcessor)->process($input);

        return $data;
    }
}
