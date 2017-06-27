<?php

namespace RZP\Gateway\Upi\Mindgate\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Mindgate;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Mindgate\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;
}
