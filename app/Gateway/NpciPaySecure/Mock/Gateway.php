<?php

namespace RZP\Gateway\NpciPaySecure\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\NpciPaySecure;

class Gateway extends NpciPaySecure\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function sendRequest($command, $params)
    {
        return (new Server())->getGatewayResponse($command, $params);
    }
}
