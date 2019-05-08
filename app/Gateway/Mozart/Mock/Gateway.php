<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart;

class Gateway extends Mozart\Gateway
{
    use Base\Mock\GatewayTrait;

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;
    }

    protected function sendGatewayRequest($request)
    {
        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        $response = $this->prepareInternalResponse($serverResponse);

        return $this->jsonToArray($response->body, true);
    }
}

