<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Base;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    public function postGatewayRequest($request, $input)
    {
        $response = (new Server())->getGatewayResponse($request);
 
        return $response;
    }
} 