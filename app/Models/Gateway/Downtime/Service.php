<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Gateway\Downtime\WebhookProcessor;

class Service extends Base\Service
{
    protected $processor;

    public function create(array $input)
    {
        $input = InputFormatter::format($input);

        $downWindow = $this->core()->create($input);

        return $downWindow->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $downWindow = $this->repo->gateway_downtime->findOrFailPublic($id);

        $downWindow = $this->core()->edit($downWindow, $input);

        return $downWindow->toArrayPublic();
    }

    public function delete($id)
    {
        $downWindow = $this->repo->gateway_downtime->findOrFailPublic($id);

        $downWindow = $this->core()->delete($downWindow);

        return $downWindow->toArrayDeleted();
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
