<?php

namespace Gateway\Cybersource\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Cybersource;
use Gateway\Base;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    public function postGatewayRequest($request, $input)
    {
        $response = (new Server())->getGatewayResponse($request);
 
        return $response;
    }
} 