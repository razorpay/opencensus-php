<?php

namespace RZP\Gateway\Hitachi\Mock;

use App;
use RZP\Gateway\Base;
use RZP\Gateway\Hitachi;

class Terminal extends Hitachi\Terminal
{
    use Base\Mock\GatewayTrait {
        Base\Mock\GatewayTrait::sendGatewayRequest as sendMockGatewayRequest;
    }

    protected function sendGatewayRequest($request)
    {
        return $this->sendMockGatewayRequest($request);
    }
}
