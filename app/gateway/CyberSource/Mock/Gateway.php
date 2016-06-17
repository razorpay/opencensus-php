<?php

namespace Gateway\Cybersource\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Cybersource;
use Gateway\Base;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return parent::authorize($input);
    }

    public function callback(array $input)
    {
        return parent::callback($input);
    }

    public function postGatewayRequest($request, $input)
    {
        $response = (new Server())->getGatewayResponse($request);
 
        return $response;
    }
} 