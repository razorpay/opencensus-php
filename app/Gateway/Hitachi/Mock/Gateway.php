<?php

namespace RZP\Gateway\Hitachi\Mock;

use App;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Gateway\Hitachi;

class Gateway extends Hitachi\Gateway
{
    use Base\Mock\GatewayTrait {
        Base\Mock\GatewayTrait::sendGatewayRequest as sendMockGatewayRequest;
    }

    protected function sendGatewayRequest($request)
    {
        $response = $this->sendMockGatewayRequest($request);

        $body = $response->body;

        return $this->parseResponseBody($body);
    }
}
