<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence\CallbackProcessor;

class Service extends Base\Service
{
    protected $processor;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $input = InputFormatter::format($input);

        $downWindow = $this->core->create($input);

        return $downWindow->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $downWindow = $this->core->edit($downWindow, $input);

        return $downWindow->toArrayPublic();
    }

    public function delete($id)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $downWindow = $this->core->delete($downWindow);

        return $downWindow->toArrayDeleted();
    }

    public function findAbsentGateways(array $input)
    {
        $absentGateways = $this->repo->gateway_absence->fetch($input);

        return $absentGateways->toArrayPublic();
    }

    public function processStatusCakeCallback(array $input)
    {
        $data = (new CallbackProcessor\StatusCakeProcessor)->process($input);

        return $data;
    }
}
