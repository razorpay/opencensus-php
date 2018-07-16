<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Axis;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Axis\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;
}
