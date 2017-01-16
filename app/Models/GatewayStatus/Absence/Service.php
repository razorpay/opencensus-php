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

        $this->processor = new Processor();
    }

    public function create(array $input)
    {
        return $this->processor->createAction($input);
    }

    public function edit($id, array $input)
    {
        return $this->processor->editAction($id, $input);
    }

    public function delete($id)
    {
        return $this->processor->deleteAction($id);
    }

    public function findAbsentGateways(array $input)
    {
        $absentGateways = $this->repo->gateway_absence->fetch($input);

        return $absentGateways->toArrayPublic();
    }

    public function processStatusCakeCallback(array $input)
    {
        //TODO: remove return from here probably. This is initiated via statuscak webhook post
        return (new CallbackProcessor\StatusCakeProcessor)->process($input);
    }
}
