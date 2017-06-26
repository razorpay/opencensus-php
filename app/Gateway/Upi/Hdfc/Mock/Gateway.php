<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Hdfc;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;
}
