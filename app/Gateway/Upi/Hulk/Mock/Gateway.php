<?php

namespace RZP\Gateway\Upi\Hulk\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Hulk;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Hulk\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;

    protected function getUrl($type = 'authorize'): string
    {
        return parent::getUrl($type);
    }

    protected function sendMgGatewayRequest($request)
    {
        return $this->sendGatewayRequest($request);
    }
}
