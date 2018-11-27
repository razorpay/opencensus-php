<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use EE\Exception;
use RZP\Gateway\Base;
use EE\Error\ErrorCode;
use RZP\Gateway\Upi\Axis;

use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Axis\Gateway
{
    use Base\Mock\GatewayTrait;
}
