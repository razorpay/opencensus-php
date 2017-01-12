<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function addPriorityForMethod(string $method, array $data)
    {
        $this->trace->info(TraceCode::ADD_GATEWAY_PRIORITY_REQUEST, [$method => $data]);

        $validator = (new Validator);

        $validator->validateMethod($method);

        $validator->validateGatewaysForMethod($method, $data);

        $validator->validateInput('add_priority', $data);

        $priority = (new Core)->addPriorityForMethod($method, $data);

        return $priority->toArray();
    }

    public function fetchPriority()
    {
        $result = (new Core)->fetchPriority();

        // $result is a collection of arrays. Running it through a flatmap to get
        // the structure as <method> => <priorities>
        $result = $result->flatMap(function ($value)
        {
            return $value;
        });

        $this->trace->info(TraceCode::FETCH_GATEWAY_PRIORITY_RESPONSE, $result->toArray());

        return $result->toArray();
    }

    public function removePriorityForMethod(string $method, array $gateways)
    {
        $this->trace->info(TraceCode::REMOVE_GATEWAY_PRIORITY_REQUEST,
                            [$method => $gateways]);

        $validatonInput = array_flip($gateways);

        $validator = (new Validator);

        $validator->validateGatewaysForMethod($method, $validatonInput);

        $priority = (new Core)->removePriorityForMethod($method, $gateways);

        return $priority->toArray();
    }
}
