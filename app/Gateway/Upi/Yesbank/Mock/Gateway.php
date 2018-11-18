<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Yesbank;

class Gateway extends Yesbank\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function getUrl($type = 'authorize'): string
    {
        return parent::getUrl($type);
    }

    protected function sendMgGatewayRequest($request)
    {
        return $this->sendGatewayRequest($request);
    }
}
